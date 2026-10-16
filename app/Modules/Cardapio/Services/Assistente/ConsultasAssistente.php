<?php

namespace App\Modules\Cardapio\Services\Assistente;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Models\PlanoSemanal;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Services\AgendaSemana;
use App\Modules\Cardapio\Services\PlanejadorService;
use App\Modules\Cardapio\Services\RecomendacaoService;
use App\Modules\Cardapio\Support\TipoRefeicao;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Carbon;

/**
 * Perguntas respondidas só com dados e regras do sistema (a IA apenas escolheu a pergunta).
 */
class ConsultasAssistente
{
    public function __construct(private readonly RecomendacaoService $recomendacao) {}

    /**
     * @param  array<string, mixed>  $consulta  ação validada
     * @return array<int, string> linhas da resposta
     */
    public function responder(Casa $casa, array $consulta): array
    {
        return match ($consulta['tipo']) {
            'consultar_estoque' => $this->estoque($casa, $consulta['dados']),
            'sugerir_receitas' => $this->sugerirReceitas($casa, $consulta['dados']),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<int, string>
     */
    private function estoque(Casa $casa, array $dados): array
    {
        $ingrediente = $casa->ingredientes()->find($dados['ingrediente_id']);

        if ($ingrediente === null) {
            return [];
        }

        $lotes = $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->orderBy('validade')->get();

        if ($lotes->isEmpty()) {
            return ["Você não tem {$ingrediente->nome} no estoque."];
        }

        $total = UnidadeConversor::formatar((float) $lotes->sum('quantidade'), $ingrediente->unidade_base);
        $detalhe = $lotes->map(fn (ItemEstoque $l) => UnidadeConversor::formatar($l->quantidade, $ingrediente->unidade_base)
            .' ('.mb_strtolower(ItemEstoque::LOCAIS[$l->local]).($l->validade ? ', vence '.$l->validade->format('d/m') : '').')')->implode('; ');

        return ["Você tem {$total} de {$ingrediente->nome}: {$detalhe}."];
    }

    /**
     * Ranqueia as receitas para uma refeição (hipotética, não salva) com o mesmo motor do planejador.
     * Ingredientes que o usuário disse ter são tratados como disponíveis.
     *
     * @param  array<string, mixed>  $dados
     * @return array<int, string>
     */
    private function sugerirReceitas(Casa $casa, array $dados): array
    {
        $data = Carbon::parse($dados['data'])->startOfDay();
        $tipo = $dados['refeicao'];

        $plano = new PlanoSemanal(['casa_id' => $casa->id, 'inicio_semana' => PlanejadorService::segundaDaSemana($data)]);
        $plano->setRelation('casa', $casa);
        $contexto = $this->recomendacao->contextoBase($plano);

        foreach ($dados['ingredientes'] as $id) {
            $contexto->estoque[$id] = max($contexto->estoque[$id] ?? 0.0, 1_000_000.0);
        }

        $pessoas = $casa->pessoas()->where('ativo', true)->with(['restricoes', 'preferencias'])->get();
        $slot = AgendaSemana::carregar($casa, $data, $data)->slot($data, $tipo);
        $participantes = $slot !== null && $slot['pessoas'] !== [] ? $pessoas->whereIn('id', $slot['pessoas'])->values() : $pessoas;
        $porcoes = max(1, $participantes->count() + ($slot['extras'] ?? 0));

        $refeicao = new RefeicaoPlanejada(['data' => $data, 'tipo_refeicao' => $tipo, 'qtd_pessoas' => $porcoes]);
        $refeicao->setRelation('pessoas', $participantes);
        $refeicao->setRelation('itens', collect());

        $ranking = $this->recomendacao->ranquear($casa->receitas()->with('ingredientes')->get(), $refeicao, $contexto);

        if ($dados['ingredientes'] !== []) {
            $ranking = $ranking->filter(fn (array $r) => array_intersect(
                array_keys($r['receita']->necessidades($porcoes, false, $r['substituicoes'])),
                $dados['ingredientes']
            ) !== [])->values();
        }

        $quem = $participantes->pluck('nome')->implode(', ');
        $linhas = ["{$this->rotulo($tipo)} de {$data->format('d/m')} para {$quem}:"];

        if ($ranking->isEmpty()) {
            $linhas[] = $dados['ingredientes'] !== []
                ? 'Nenhuma receita cadastrada combina com esses ingredientes e as restrições de quem vai comer. Cadastre receitas que os usem.'
                : 'Nenhuma receita cadastrada serve para essa refeição. Cadastre receitas marcando para qual refeição servem.';
        }

        foreach ($ranking->take(5) as $i => $r) {
            $extras = array_slice($r['motivos'], 0, 2);
            $aviso = $r['avisos'][0] ?? null;
            $linhas[] = ($i + 1).'. '.$r['receita']->nome.($extras !== [] ? ' — '.implode(' ', $extras) : '').($aviso ? " ⚠ {$aviso}" : '');
        }

        foreach ($dados['desconhecidos'] as $nome) {
            $linhas[] = "Obs.: “{$nome}” não está nos seus ingredientes, então não entrou na conta.";
        }

        return $linhas;
    }

    private function rotulo(string $tipo): string
    {
        return TipoRefeicao::rotulos()[$tipo] ?? $tipo;
    }
}
