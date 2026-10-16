<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Exceptions\EstoqueInsuficienteException;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Services\EstoqueService;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class EstoqueController extends Controller
{
    public function __construct(private readonly EstoqueService $estoque) {}

    public function index(Request $request): View
    {
        $casa = $this->casa($request);
        $local = $request->query('local');
        $busca = trim((string) $request->query('q', ''));

        $itens = $casa->itensEstoque()->with('ingrediente')
            ->when(array_key_exists((string) $local, ItemEstoque::LOCAIS), fn ($q) => $q->where('local', $local))
            ->when($busca !== '', fn ($q) => $q->whereHas('ingrediente', fn ($i) => $i->whereRaw('lower(nome) like ?', ['%'.mb_strtolower($busca).'%'])))
            ->get()
            ->sortBy(fn (ItemEstoque $i) => mb_strtolower($i->ingrediente->nome));

        return view('Cardapio::estoque.index', [
            'itens' => $itens,
            'local' => $local,
            'busca' => $busca,
            'vencendo' => $this->estoque->vencendo($casa),
            'estoqueBaixo' => $this->estoque->estoqueBaixo($casa),
        ]);
    }

    public function create(Request $request): View
    {
        return view('Cardapio::estoque.form', $this->dadosForm($request, new ItemEstoque(['local' => 'despensa'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $casa = $this->casa($request);
        $dados = $this->validar($request);
        $ingrediente = $casa->ingredientes()->findOrFail($dados['ingrediente_id']);

        try {
            $this->estoque->adicionar($casa, $ingrediente, (float) $dados['quantidade'], $dados['unidade'], $this->atributos($request, $dados));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['unidade' => $e->getMessage()])->withInput();
        }

        return redirect()->route('cardapio.estoque.index')->with('status', 'Item adicionado ao estoque.');
    }

    public function edit(Request $request, ItemEstoque $item): View
    {
        $this->autorizar($request, $item);

        return view('Cardapio::estoque.form', $this->dadosForm($request, $item));
    }

    public function update(Request $request, ItemEstoque $item): RedirectResponse
    {
        $this->autorizar($request, $item);
        $dados = $this->validar($request);
        $ingrediente = $this->casa($request)->ingredientes()->findOrFail($dados['ingrediente_id']);

        if (! UnidadeConversor::compativel($dados['unidade'], $ingrediente->unidade_base)) {
            return back()->withErrors(['unidade' => 'Unidade incompatível com o ingrediente.'])->withInput();
        }

        $item->update(['ingrediente_id' => $ingrediente->id] + $this->atributos($request, $dados));
        $this->estoque->ajustar($item, UnidadeConversor::paraBase((float) $dados['quantidade'], $dados['unidade']));

        return redirect()->route('cardapio.estoque.index')->with('status', 'Item atualizado.');
    }

    public function baixar(Request $request, ItemEstoque $item): RedirectResponse
    {
        $this->autorizar($request, $item);
        $dados = $request->validate([
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(array_keys(UnidadeConversor::rotulos()))],
        ]);

        if (! UnidadeConversor::compativel($dados['unidade'], $item->ingrediente->unidade_base)) {
            return back()->withErrors(['unidade' => 'Unidade incompatível com o ingrediente.']);
        }

        try {
            $this->estoque->ajustar($item, $item->quantidade - UnidadeConversor::paraBase((float) $dados['quantidade'], $dados['unidade']));
        } catch (EstoqueInsuficienteException) {
            return back()->withErrors(['quantidade' => 'Isso é mais do que existe no estoque.']);
        }

        return back()->with('status', 'Estoque atualizado.');
    }

    public function destroy(Request $request, ItemEstoque $item): RedirectResponse
    {
        $this->autorizar($request, $item);
        $item->delete();

        return redirect()->route('cardapio.estoque.index')->with('status', 'Item removido.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, ItemEstoque $item): void
    {
        abort_unless($item->casa_id === $this->casa($request)->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosForm(Request $request, ItemEstoque $item): array
    {
        $ingredientes = $this->casa($request)->ingredientes()->orderBy('nome')->get();
        [$valor, $unidade] = $item->exists
            ? UnidadeConversor::legivel($item->quantidade, $item->ingrediente->unidade_base)
            : [null, 'g'];

        return compact('item', 'ingredientes', 'valor', 'unidade');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'ingrediente_id' => ['required', 'integer'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(array_keys(UnidadeConversor::rotulos()))],
            'local' => ['required', Rule::in(array_keys(ItemEstoque::LOCAIS))],
            'validade' => ['nullable', 'date'],
            'marca' => ['nullable', 'string', 'max:120'],
            'lote' => ['nullable', 'string', 'max:60'],
            'minimo' => ['nullable', 'numeric', 'gte:0'],
            'ideal' => ['nullable', 'numeric', 'gte:0'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * Atributos do lote (sem a quantidade); mínimo/ideal chegam na unidade escolhida e vão para a base.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function atributos(Request $request, array $dados): array
    {
        $paraBase = fn (mixed $v): ?float => ($v === null || $v === '') ? null : UnidadeConversor::paraBase((float) $v, $dados['unidade']);

        return [
            'local' => $dados['local'],
            'validade' => $dados['validade'] ?? null,
            'marca' => $dados['marca'] ?? null,
            'lote' => $dados['lote'] ?? null,
            'aproximada' => $request->boolean('aproximada'),
            'quantidade_minima' => $paraBase($dados['minimo'] ?? null),
            'quantidade_ideal' => $paraBase($dados['ideal'] ?? null),
            'observacoes' => $dados['observacoes'] ?? null,
        ];
    }
}
