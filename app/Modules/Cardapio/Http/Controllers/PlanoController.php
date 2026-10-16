<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\PlanoSemanal;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Services\ComprasService;
use App\Modules\Cardapio\Services\PlanejadorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function __construct(
        private readonly PlanejadorService $planejador,
        private readonly ComprasService $compras,
    ) {}

    public function index(Request $request): View
    {
        $casa = $this->casa($request);
        $segunda = PlanejadorService::segundaDaSemana(now());

        return view('Cardapio::planejamento.index', [
            'planos' => $casa->planos()->withCount('refeicoes')->orderByDesc('inicio_semana')->get(),
            'semanaAtual' => $segunda,
            'proximaSemana' => $segunda->copy()->addWeek(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate(['semana' => ['required', 'date']]);
        $plano = $this->planejador->criarSemana($this->casa($request), $dados['semana']);

        return redirect()->route('cardapio.planejamento.show', $plano);
    }

    public function show(Request $request, PlanoSemanal $plano): View
    {
        $this->autorizar($request, $plano);
        $casa = $this->casa($request);

        return view('Cardapio::planejamento.show', [
            'plano' => $plano,
            'dias' => $plano->refeicoes()->with(['receita', 'pessoas', 'itens.receita'])->orderBy('data')->get()
                ->sortBy(fn (RefeicaoPlanejada $r) => $r->data->toDateString().'|'.array_search($r->tipo_refeicao, array_keys(\App\Modules\Cardapio\Support\TipoRefeicao::rotulos())))
                ->groupBy(fn (RefeicaoPlanejada $r) => $r->data->toDateString()),
            'receitas' => $casa->receitas()->orderBy('nome')->get(),
            'pessoas' => $casa->pessoas()->where('ativo', true)->orderBy('nome')->get(),
            'lista' => $plano->listaCompras()->latest('id')->first(),
            'pontuacao' => $this->planejador->pontuacaoSemana($plano),
        ]);
    }

    public function salvar(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $casa = $this->casa($request);
        $receitasValidas = $casa->receitas()->pluck('id')->all();
        $pessoasValidas = $casa->pessoas()->pluck('id')->all();

        $dados = $request->validate([
            'receitas' => ['nullable', 'array'],
            'receitas.*' => ['nullable', 'integer'],
            'pessoas' => ['nullable', 'array'],
            'pessoas.*' => ['nullable', 'array'],
            'pessoas.*.*' => ['integer'],
            'itens' => ['nullable', 'array'],
            'itens.*' => ['nullable', 'array'],
            'itens.*.*' => ['integer'],
        ]);

        $avisos = [];

        DB::transaction(function () use ($plano, $dados, $receitasValidas, $pessoasValidas, &$avisos): void {
            $pessoasMudaram = [];

            // Primeiro as pessoas (as restrições dependem de quem come), depois as receitas.
            foreach ($plano->refeicoes()->with('pessoas')->get() as $refeicao) {
                if ($refeicao->consumida() || ! array_key_exists($refeicao->id, $dados['pessoas'] ?? [])) {
                    continue;
                }

                $ids = array_values(array_intersect(array_map('intval', $dados['pessoas'][$refeicao->id]), $pessoasValidas));
                $atuais = $refeicao->pessoas->pluck('id')->sort()->values()->all();

                if (collect($ids)->sort()->values()->all() === $atuais) {
                    continue;
                }

                $refeicao->pessoas()->sync($ids);
                $refeicao->update(['qtd_pessoas' => count($ids) + $refeicao->pessoas_extras, 'pessoas_manual' => true]);
                $pessoasMudaram[$refeicao->id] = true;
            }

            foreach ($plano->refeicoes()->get() as $refeicao) {
                if ($refeicao->consumida() || ! array_key_exists($refeicao->id, $dados['receitas'] ?? [])) {
                    continue;
                }

                $receitaId = (int) $dados['receitas'][$refeicao->id];
                $novoId = in_array($receitaId, $receitasValidas, true) ? $receitaId : null;
                if ($novoId === $refeicao->receita_id && ! ($novoId && isset($pessoasMudaram[$refeicao->id]))) {
                    continue;
                }

                $avaliacao = $this->planejador->escolher($refeicao, $novoId ? Receita::find($novoId) : null);

                foreach ($avaliacao['avisos'] ?? [] as $aviso) {
                    if (($avaliacao['compativel'] ?? true) === false) {
                        $avisos[] = $aviso;
                    }
                }
            }
        });

        foreach ($plano->refeicoes()->with('itens')->get() as $refeicao) {
            if ($refeicao->consumida() || ! array_key_exists($refeicao->id, $dados['itens'] ?? [])) {
                continue;
            }

            $novos = collect($dados['itens'][$refeicao->id])->map(fn ($id) => (int) $id)->filter(fn ($id) => in_array($id, $receitasValidas, true))->unique()->sort()->values()->all();

            if ($novos !== $refeicao->itens->pluck('receita_id')->sort()->values()->all()) {
                $avisos = array_merge($avisos, $this->planejador->definirItens($refeicao, $novos));
            }
        }

        $this->recalcularCompras($plano);

        if ($avisos !== []) {
            return redirect()->route('cardapio.planejamento.show', $plano)->with('status', 'Cardápio salvo.')->withErrors($avisos);
        }

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', 'Cardápio salvo.');
    }

    public function consumir(Request $request, PlanoSemanal $plano, RefeicaoPlanejada $refeicao): RedirectResponse
    {
        $this->autorizar($request, $plano);
        abort_unless($refeicao->plano_id === $plano->id, 404);

        if (! $refeicao->receita_id) {
            return back()->withErrors(['refeicao' => 'Escolha e salve uma receita antes de marcar como feita.']);
        }

        $this->planejador->consumir($refeicao);

        return back()->with('status', 'Refeição registrada e ingredientes baixados do estoque.');
    }

    public function gerarCompras(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $lista = $this->compras->gerarParaPlano($plano);

        return redirect()->route('cardapio.compras.show', $lista)->with('status', 'Lista de compras calculada com base no estoque.');
    }

    public function destroy(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $plano->delete();

        return redirect()->route('cardapio.planejamento.index')->with('status', 'Semana removida.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, PlanoSemanal $plano): void
    {
        abort_unless($plano->casa_id === $this->casa($request)->id, 404);
    }

    public function otimizar(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $r = $this->planejador->otimizar($plano);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', $r['melhorias'] > 0
            ? "Semana otimizada: {$r['melhorias']} troca(s), pontuação de ".round($r['antes']).' para '.round($r['depois']).'.'
            : 'A semana já está bem otimizada: nenhuma troca melhora o conjunto. (Escolhas feitas por você e a lancheira não são trocadas.)');
    }

    public function sincronizar(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $r = $this->planejador->sincronizar($plano, refazerPessoas: true);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)
            ->with('status', "Semana sincronizada com rotina, ausências e eventos: {$r['criadas']} criada(s), {$r['removidas']} removida(s), {$r['atualizadas']} ajustada(s).");
    }

    public function gerar(Request $request, PlanoSemanal $plano): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $tudo = $request->boolean('tudo');

        $r = $this->planejador->gerar($plano, null, limpar: $tudo);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', $this->resumo($r));
    }

    public function gerarDia(Request $request, PlanoSemanal $plano, string $data): RedirectResponse
    {
        $this->autorizar($request, $plano);
        $alvo = $plano->refeicoes()->whereDate('data', $data)->get();
        abort_if($alvo->isEmpty(), 404);

        $r = $this->planejador->gerar($plano, $alvo, limpar: true);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', $this->resumo($r));
    }

    public function gerarRefeicao(Request $request, PlanoSemanal $plano, RefeicaoPlanejada $refeicao): RedirectResponse
    {
        $this->autorizar($request, $plano);
        abort_unless($refeicao->plano_id === $plano->id, 404);

        $r = $this->planejador->gerar($plano, collect([$refeicao]), limpar: true);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', $this->resumo($r));
    }

    public function alternativas(Request $request, PlanoSemanal $plano, RefeicaoPlanejada $refeicao): View
    {
        $this->autorizar($request, $plano);
        abort_unless($refeicao->plano_id === $plano->id, 404);
        abort_if($refeicao->consumida(), 404);

        return view('Cardapio::planejamento.alternativas', [
            'plano' => $plano,
            'refeicao' => $refeicao->load('receita', 'pessoas'),
            'alternativas' => $this->planejador->alternativas($refeicao),
        ]);
    }

    public function escolher(Request $request, PlanoSemanal $plano, RefeicaoPlanejada $refeicao): RedirectResponse
    {
        $this->autorizar($request, $plano);
        abort_unless($refeicao->plano_id === $plano->id, 404);
        abort_if($refeicao->consumida(), 404);

        $dados = $request->validate(['receita_id' => ['required', 'integer']]);
        $receita = $this->casa($request)->receitas()->findOrFail($dados['receita_id']);

        $this->planejador->escolher($refeicao, $receita);
        $this->recalcularCompras($plano);

        return redirect()->route('cardapio.planejamento.show', $plano)->with('status', "Receita trocada por {$receita->nome}.");
    }

    /**
     * Mudou o cardápio: se já existe lista aberta para a semana, recalcula o déficit.
     */
    private function recalcularCompras(PlanoSemanal $plano): void
    {
        if ($plano->listaCompras()->where('status', 'aberta')->exists()) {
            $this->compras->gerarParaPlano($plano);
        }
    }

    /**
     * @param  array{preenchidas: int, sem_opcao: int, melhorias?: int}  $r
     */
    private function resumo(array $r): string
    {
        $texto = "{$r['preenchidas']} refeição(ões) planejada(s)".(($r['melhorias'] ?? 0) > 0 ? " ({$r['melhorias']} ajustada(s) pela otimização da semana)" : '');

        return $r['sem_opcao'] > 0
            ? "{$texto}; {$r['sem_opcao']} sem receita compatível (cadastre receitas para esse tipo de refeição ou revise as restrições)."
            : $texto.'.';
    }
}
