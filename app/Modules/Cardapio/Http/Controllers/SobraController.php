<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Services\SobraService;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SobraController extends Controller
{
    public function __construct(private readonly SobraService $sobras) {}

    public function index(Request $request): View
    {
        $casa = $this->casa($request);
        $refeicao = $request->integer('refeicao')
            ? $casa->planos()->with('refeicoes.receita')->get()->pluck('refeicoes')->flatten()->firstWhere('id', $request->integer('refeicao'))
            : null;

        return view('Cardapio::sobras.index', [
            'lotes' => $casa->itensEstoque()->with('ingrediente')->whereHas('ingrediente', fn ($q) => $q->where('sobra', true))->orderBy('validade')->get(),
            'registradas' => $casa->sobras()->with(['ingrediente', 'receita'])->latest('id')->limit(15)->get(),
            'receitas' => $casa->receitas()->orderBy('nome')->get(),
            'refeicao' => $refeicao,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $casa = $this->casa($request);
        $dados = $request->validate([
            'receita_id' => ['nullable', 'integer'],
            'refeicao_id' => ['nullable', 'integer'],
            'nome' => ['nullable', 'string', 'max:120'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(array_keys(UnidadeConversor::rotulos()))],
            'dias' => ['required', 'integer', 'between:1,30'],
        ]);

        $receita = ! empty($dados['receita_id']) ? $casa->receitas()->find($dados['receita_id']) : null;
        $refeicao = ! empty($dados['refeicao_id'])
            ? $casa->planos()->with('refeicoes')->get()->pluck('refeicoes')->flatten()->firstWhere('id', (int) $dados['refeicao_id'])
            : null;
        $nome = trim((string) ($dados['nome'] ?? '')) ?: $receita?->nome;

        if ($nome === null || $nome === '') {
            return back()->withErrors(['nome' => 'Escolha a receita ou descreva a sobra.'])->withInput();
        }

        $this->sobras->registrar($casa, $nome, (float) $dados['quantidade'], $dados['unidade'], (int) $dados['dias'], $receita, $refeicao);

        return redirect()->route('cardapio.sobras.index')
            ->with('status', "Sobra guardada no estoque como “Sobra de {$nome}”. Use esse ingrediente em receitas (sanduíche, torta, recheio…) para aproveitá-la.");
    }

    public function destroy(Request $request, ItemEstoque $item): RedirectResponse
    {
        abort_unless($item->casa_id === $this->casa($request)->id && $item->ingrediente->sobra, 404);
        $item->delete();

        return back()->with('status', 'Sobra descartada.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }
}
