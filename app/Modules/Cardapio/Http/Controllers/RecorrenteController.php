<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ProdutoRecorrente;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecorrenteController extends Controller
{
    public function index(Request $request): View
    {
        $casa = $this->casa($request);

        return view('Cardapio::recorrentes.index', [
            'produtos' => $casa->produtosRecorrentes()->with('ingrediente')->orderByDesc('ativo')->get()
                ->sortBy(fn (ProdutoRecorrente $p) => mb_strtolower($p->nome()))->values(),
            'ingredientes' => $casa->ingredientes()->where('sobra', false)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $casa = $this->casa($request);
        $dados = $request->validate([
            'ingrediente_id' => ['nullable', 'integer'],
            'descricao' => ['nullable', 'string', 'max:160'],
            'quantidade' => ['nullable', 'numeric', 'gt:0'],
            'unidade' => ['nullable', Rule::in(array_keys(UnidadeConversor::rotulos()))],
        ]);

        $ingrediente = ! empty($dados['ingrediente_id']) ? $casa->ingredientes()->find($dados['ingrediente_id']) : null;

        if ($ingrediente === null && empty($dados['descricao'])) {
            return back()->withErrors(['descricao' => 'Escolha um ingrediente ou descreva o produto (ex.: papel higiênico).'])->withInput();
        }

        $quantidade = null;
        if ($ingrediente !== null) {
            if (empty($dados['quantidade']) || empty($dados['unidade']) || ! UnidadeConversor::compativel($dados['unidade'], $ingrediente->unidade_base)) {
                return back()->withErrors(['quantidade' => 'Informe a quantidade desejada em uma unidade compatível com o ingrediente.'])->withInput();
            }
            $quantidade = UnidadeConversor::paraBase((float) $dados['quantidade'], $dados['unidade']);
        }

        $casa->produtosRecorrentes()->create([
            'ingrediente_id' => $ingrediente?->id,
            'descricao' => $ingrediente ? null : $dados['descricao'],
            'quantidade' => $quantidade,
        ]);

        return redirect()->route('cardapio.recorrentes.index')->with('status', 'Produto recorrente cadastrado.');
    }

    public function alternar(Request $request, ProdutoRecorrente $produto): RedirectResponse
    {
        abort_unless($produto->casa_id === $this->casa($request)->id, 404);
        $produto->update(['ativo' => ! $produto->ativo]);

        return back();
    }

    public function destroy(Request $request, ProdutoRecorrente $produto): RedirectResponse
    {
        abort_unless($produto->casa_id === $this->casa($request)->id, 404);
        $produto->delete();

        return back()->with('status', 'Produto removido.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }
}
