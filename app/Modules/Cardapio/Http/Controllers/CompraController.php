<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemCompra;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Models\ListaCompras;
use App\Modules\Cardapio\Services\ComprasService;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function __construct(private readonly ComprasService $compras) {}

    public function index(Request $request): View
    {
        return view('Cardapio::compras.index', [
            'listas' => $this->casa($request)->listasCompras()->withCount(['itens', 'itens as pendentes_count' => fn ($q) => $q->where('comprado', false)])
                ->orderByRaw("status = 'aberta' desc")->orderByDesc('id')->get(),
        ]);
    }

    public function recorrentes(Request $request): RedirectResponse
    {
        $lista = $this->compras->gerarRecorrentes($this->casa($request));

        return redirect()->route('cardapio.compras.show', $lista)->with('status', 'Lista dos produtos recorrentes calculada.');
    }

    public function store(Request $request): RedirectResponse
    {
        $lista = $this->casa($request)->listasCompras()->create(['titulo' => 'Compras avulsas']);

        return redirect()->route('cardapio.compras.show', $lista);
    }

    public function show(Request $request, ListaCompras $lista): View
    {
        $this->autorizar($request, $lista);
        $casa = $this->casa($request);

        $itens = $lista->itens()->with('ingrediente.categoria')->get();
        $grupos = $itens->sortBy(fn (ItemCompra $i) => mb_strtolower($i->nome()))
            ->groupBy(fn (ItemCompra $i) => $i->ingrediente?->categoria?->nome ?? ($i->manual ? 'Itens manuais' : 'Outros'));

        return view('Cardapio::compras.show', [
            'lista' => $lista,
            'grupos' => $grupos,
            'ingredientes' => $casa->ingredientes()->orderBy('nome')->get(),
            'paraAdicionar' => $itens->where('comprado', true)->where('adicionado_ao_estoque', false)->whereNotNull('ingrediente_id')->count(),
        ]);
    }

    public function adicionarItem(Request $request, ListaCompras $lista): RedirectResponse
    {
        $this->autorizar($request, $lista);
        $dados = $request->validate(['descricao' => ['required', 'string', 'max:160']]);

        $lista->itens()->create(['descricao' => $dados['descricao'], 'manual' => true]);

        return back()->with('status', 'Item adicionado.');
    }

    public function alternarComprado(Request $request, ListaCompras $lista, ItemCompra $item): RedirectResponse
    {
        $this->autorizarItem($request, $lista, $item);
        $item->update(['comprado' => ! $item->comprado]);

        return back();
    }

    public function atualizarQuantidade(Request $request, ListaCompras $lista, ItemCompra $item): RedirectResponse
    {
        $this->autorizarItem($request, $lista, $item);
        abort_if($item->ingrediente_id === null, 404);

        $dados = $request->validate([
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(array_keys(UnidadeConversor::rotulos()))],
        ]);

        if (! UnidadeConversor::compativel($dados['unidade'], $item->ingrediente->unidade_base)) {
            return back()->withErrors(['unidade' => 'Unidade incompatível com o ingrediente.']);
        }

        $item->update(['quantidade_comprar' => UnidadeConversor::paraBase((float) $dados['quantidade'], $dados['unidade'])]);

        return back()->with('status', 'Quantidade alterada.');
    }

    /**
     * Passa o item para a próxima compra (outra lista aberta; cria uma se não houver).
     */
    public function mover(Request $request, ListaCompras $lista, ItemCompra $item): RedirectResponse
    {
        $this->autorizarItem($request, $lista, $item);
        $casa = $this->casa($request);

        $destino = $casa->listasCompras()->where('status', 'aberta')->whereKeyNot($lista->id)->latest('id')->first()
            ?? $casa->listasCompras()->create(['titulo' => 'Próxima compra']);

        // Vira item manual para não ser apagado quando a lista de origem/destino for recalculada.
        $item->update(['lista_id' => $destino->id, 'manual' => true, 'comprado' => false]);

        return back()->with('status', "“{$item->nome()}” movido para “".($destino->titulo ?? 'Lista #'.$destino->id).'”.');
    }

    public function removerItem(Request $request, ListaCompras $lista, ItemCompra $item): RedirectResponse
    {
        $this->autorizarItem($request, $lista, $item);
        $item->delete();

        return back();
    }

    public function adicionarAoEstoque(Request $request, ListaCompras $lista): RedirectResponse
    {
        $this->autorizar($request, $lista);
        $dados = $request->validate(['local' => ['required', Rule::in(array_keys(ItemEstoque::LOCAIS))]]);

        $quantos = $this->compras->adicionarCompradosAoEstoque($lista, $dados['local']);

        return back()->with('status', $quantos > 0 ? "{$quantos} item(ns) adicionados ao estoque." : 'Nada novo para adicionar ao estoque.');
    }

    public function concluir(Request $request, ListaCompras $lista): RedirectResponse
    {
        $this->autorizar($request, $lista);
        $lista->update(['status' => $lista->status === 'aberta' ? 'concluida' : 'aberta']);

        return redirect()->route('cardapio.compras.index');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, ListaCompras $lista): void
    {
        abort_unless($lista->casa_id === $this->casa($request)->id, 404);
    }

    private function autorizarItem(Request $request, ListaCompras $lista, ItemCompra $item): void
    {
        $this->autorizar($request, $lista);
        abort_unless($item->lista_id === $lista->id, 404);
    }
}
