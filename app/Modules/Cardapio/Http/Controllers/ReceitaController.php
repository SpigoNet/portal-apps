<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Support\TipoRefeicao;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceitaController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('q', ''));

        $receitas = $this->casa($request)->receitas()
            ->when($busca !== '', fn ($q) => $q->whereRaw('lower(nome) like ?', ['%'.mb_strtolower($busca).'%']))
            ->when($request->boolean('favoritas'), fn ($q) => $q->where('favorita', true))
            ->orderByDesc('favorita')->orderBy('nome')
            ->get();

        return view('Cardapio::receitas.index', [
            'receitas' => $receitas,
            'busca' => $busca,
            'soFavoritas' => $request->boolean('favoritas'),
        ]);
    }

    public function show(Request $request, Receita $receita): View
    {
        $this->autorizar($request, $receita);
        $receita->load('ingredientes.ingrediente');
        $porcoes = max(1, (int) $request->query('porcoes', $receita->rendimento));

        return view('Cardapio::receitas.show', [
            'receita' => $receita,
            'porcoes' => $porcoes,
            'fator' => $porcoes / max(1, $receita->rendimento),
            'feitas' => $receita->historicos()->orderByDesc('consumida_em')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('Cardapio::receitas.form', $this->dadosForm($request, new Receita([
            'rendimento' => 4, 'dificuldade' => 'media', 'frequencia' => 'media', 'refeicoes_compativeis' => [],
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $receita = DB::transaction(function () use ($request): Receita {
            $receita = $this->casa($request)->receitas()->create($this->atributos($request, null));
            $this->gravarIngredientes($request, $receita);

            return $receita;
        });

        return redirect()->route('cardapio.receitas.show', $receita)->with('status', 'Receita cadastrada.');
    }

    public function edit(Request $request, Receita $receita): View
    {
        $this->autorizar($request, $receita);

        return view('Cardapio::receitas.form', $this->dadosForm($request, $receita));
    }

    public function update(Request $request, Receita $receita): RedirectResponse
    {
        $this->autorizar($request, $receita);

        DB::transaction(function () use ($request, $receita): void {
            $receita->update($this->atributos($request, $receita));
            $this->gravarIngredientes($request, $receita);
        });

        return redirect()->route('cardapio.receitas.show', $receita)->with('status', 'Receita atualizada.');
    }

    public function destroy(Request $request, Receita $receita): RedirectResponse
    {
        $this->autorizar($request, $receita);
        $receita->delete();

        return redirect()->route('cardapio.receitas.index')->with('status', 'Receita removida.');
    }

    public function favoritar(Request $request, Receita $receita): RedirectResponse
    {
        $this->autorizar($request, $receita);
        $receita->update(['favorita' => ! $receita->favorita]);

        return back();
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, Receita $receita): void
    {
        abort_unless($receita->casa_id === $this->casa($request)->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosForm(Request $request, Receita $receita): array
    {
        $ingredientes = $this->casa($request)->ingredientes()->orderBy('nome')->get();
        $linhas = $receita->exists
            ? $receita->ingredientes->map(function ($l) {
                [$valor, $unidade] = UnidadeConversor::legivel($l->quantidade, $l->ingrediente->unidade_base);

                return ['ingrediente_id' => $l->ingrediente_id, 'quantidade' => $valor, 'unidade' => $unidade, 'opcional' => $l->opcional];
            })->values()->all()
            : [];

        return compact('receita', 'ingredientes', 'linhas');
    }

    /**
     * @return array<string, mixed>
     */
    private function atributos(Request $request, ?Receita $receita): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:160', Rule::unique('cardapio_receitas', 'nome')->where('casa_id', $this->casa($request)->id)->ignore($receita?->id)],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'rendimento' => ['required', 'integer', 'between:1,100'],
            'tempo_preparo' => ['nullable', 'integer', 'between:1,1440'],
            'dificuldade' => ['required', Rule::in(array_keys(Receita::DIFICULDADES))],
            'frequencia' => ['required', Rule::in(array_keys(Receita::FREQUENCIAS))],
            'modo_preparo' => ['nullable', 'string', 'max:10000'],
            'categorias' => ['nullable', 'string', 'max:255'],
            'refeicoes_compativeis' => ['nullable', 'array'],
            'refeicoes_compativeis.*' => [Rule::in(array_keys(TipoRefeicao::rotulos()))],
        ]);

        $dados['categorias'] = collect(explode(',', (string) ($dados['categorias'] ?? '')))
            ->map(fn (string $c) => trim($c))->filter()->unique()->values()->all();
        $dados['refeicoes_compativeis'] = array_values($dados['refeicoes_compativeis'] ?? []);
        $dados['favorita'] = $request->boolean('favorita');

        return $dados;
    }

    private function gravarIngredientes(Request $request, Receita $receita): void
    {
        $linhas = $request->validate([
            'ingredientes' => ['required', 'array', 'min:1'],
            'ingredientes.*.ingrediente_id' => ['required', 'integer'],
            'ingredientes.*.quantidade' => ['required', 'numeric', 'gt:0'],
            'ingredientes.*.unidade' => ['required', Rule::in(array_keys(UnidadeConversor::rotulos()))],
            'ingredientes.*.opcional' => ['nullable'],
        ])['ingredientes'];

        $ingredientes = $this->casa($request)->ingredientes()->get()->keyBy('id');
        $gravar = [];

        foreach ($linhas as $i => $linha) {
            $ingrediente = $ingredientes->get((int) $linha['ingrediente_id']);

            if ($ingrediente === null || ! UnidadeConversor::compativel($linha['unidade'], $ingrediente->unidade_base)) {
                throw ValidationException::withMessages(["ingredientes.{$i}.unidade" => 'Ingrediente ou unidade inválidos na linha '.($i + 1).'.']);
            }

            $gravar[] = [
                'ingrediente_id' => $ingrediente->id,
                'quantidade' => UnidadeConversor::paraBase((float) $linha['quantidade'], $linha['unidade']),
                'opcional' => ! empty($linha['opcional']),
            ];
        }

        $receita->ingredientes()->delete();
        $receita->ingredientes()->createMany($gravar);
    }
}
