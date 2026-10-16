<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\HistoricoRefeicao;
use App\Modules\Cardapio\Models\PlanoSemanal;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Models\SubstituicaoIngrediente;
use Illuminate\Support\Collection;

/**
 * Motor de recomendação: filtra receitas incompatíveis (RN01), adapta por substituição (RN02) e pontua.
 *
 *   score = estoque*30 + validade*20 + preferencia*15 + favorito*15 + variedade*10 + praticidade*10
 *           + compartilhado*5 + feedback*10 + aprendizado*8 − repeticao*15 − compras*10
 */
class RecomendacaoService
{
    /** @var array<string, int> */
    public const PESOS = [
        'estoque' => 30, 'validade' => 20, 'preferencia' => 15, 'favorito' => 15, 'variedade' => 10,
        'praticidade' => 10, 'compartilhado' => 5, 'feedback' => 10, 'aprendizado' => 8, 'repeticao' => 15, 'compras' => 10,
    ];

    /** Janela (dias) em que o uso recente de uma receita pesa contra ela. */
    public const JANELA_REPETICAO = 10;

    /**
     * Monta o estado da semana. Refeições com receita (exceto $ignorar) já consomem o estoque virtual.
     */
    public function contexto(PlanoSemanal $plano, ?RefeicaoPlanejada $ignorar = null): ContextoPlanejamento
    {
        $contexto = $this->contextoBase($plano);

        $pendentes = $plano->refeicoes()
            ->whereNull('consumida_em')->whereNotNull('receita_id')
            ->when($ignorar, fn ($q) => $q->whereKeyNot($ignorar->id))
            ->with(['receita.ingredientes', 'itens.receita.ingredientes'])->get();

        foreach ($pendentes as $refeicao) {
            $contexto->registrarRefeicao($refeicao);
        }

        return $contexto;
    }

    /**
     * Estado sem as refeições pendentes da semana: estoque real (sem vencidos), validades, substituições,
     * feedback e histórico recente (semanas anteriores e refeições já feitas).
     */
    public function contextoBase(PlanoSemanal $plano): ContextoPlanejamento
    {
        $casa = $plano->casa;
        $estoque = [];
        $validade = [];

        foreach ($casa->itensEstoque()->get() as $item) {
            $dias = $item->diasParaVencer();

            if ($dias !== null && $dias < 0) {
                continue; // vencido não conta como disponível
            }

            $estoque[$item->ingrediente_id] = ($estoque[$item->ingrediente_id] ?? 0.0) + $item->quantidade;

            if ($dias !== null) {
                $validade[$item->ingrediente_id] = min($validade[$item->ingrediente_id] ?? PHP_INT_MAX, $dias);
            }
        }

        $substituicoes = [];
        foreach (SubstituicaoIngrediente::where('casa_id', $casa->id)->orderByDesc('compatibilidade')->get() as $s) {
            $substituicoes[$s->ingrediente_id][] = ['id' => $s->substituto_id, 'compatibilidade' => $s->compatibilidade];
        }

        $contexto = new ContextoPlanejamento($estoque, $validade, $casa->ingredientes()->get()->keyBy('id'), $substituicoes);

        foreach (HistoricoRefeicao::where('casa_id', $casa->id)->whereNotNull('receita_id')->orderByDesc('consumida_em')->get()->groupBy('receita_id') as $receitaId => $feitas) {
            $valores = $feitas->map(fn (HistoricoRefeicao $h) => $h->valor())->filter(fn ($v) => $v !== null)->take(5);

            if ($valores->isNotEmpty()) {
                $contexto->feedback[$receitaId] = (float) $valores->avg();
            }

            if ($feitas->first(fn (HistoricoRefeicao $h) => $h->feedback !== null)?->feedback === 'nao_fazer_novamente') {
                $contexto->bloqueadas[] = $receitaId;
            }
        }

        $recentes = RefeicaoPlanejada::query()
            ->whereHas('plano', fn ($q) => $q->where('casa_id', $casa->id))
            ->whereNotNull('receita_id')
            ->whereDate('data', '>=', $plano->inicio_semana->copy()->subDays(self::JANELA_REPETICAO))
            ->whereDate('data', '<=', $plano->fimSemana())
            ->with(['receita.ingredientes', 'itens.receita.ingredientes'])
            ->get();

        foreach ($recentes as $refeicao) {
            if ($refeicao->plano_id === $plano->id && ! $refeicao->consumida()) {
                continue; // pendente desta semana: entra por `contexto()` / pela otimização
            }

            // Passado ou já consumido (o estoque real já reflete): só histórico.
            $contexto->usos[] = [
                'data' => $refeicao->data,
                'receita_id' => $refeicao->receita_id,
                'principal' => ContextoPlanejamento::principal($refeicao->necessidades()),
            ];

            foreach ($refeicao->itens as $item) {
                $contexto->usos[] = ['data' => $refeicao->data, 'receita_id' => $item->receita_id, 'principal' => null];
            }
        }

        return $contexto;
    }

    /**
     * Avalia uma receita para uma refeição.
     *
     * @return array{receita: Receita, compativel: bool, score: float, substituicoes: array<int, int>, motivos: array<int, string>, avisos: array<int, string>}
     */
    public function avaliar(Receita $receita, RefeicaoPlanejada $refeicao, ContextoPlanejamento $ctx): array
    {
        $pessoas = $refeicao->pessoas;
        $porcoes = max(1, $refeicao->qtd_pessoas ?: $pessoas->count());
        $motivos = [];
        $avisos = [];

        // 1. Restrições obrigatórias (alergia/intolerância): ingrediente => nomes de quem restringe.
        $bloqueados = [];
        foreach ($pessoas as $pessoa) {
            foreach ($pessoa->restricoes as $restricao) {
                if ($restricao->ingrediente_id && in_array($restricao->tipo, ['alergia', 'intolerancia'], true)) {
                    $bloqueados[$restricao->ingrediente_id][] = $pessoa->nome;
                }
            }
        }

        $fator = $porcoes / max(1, $receita->rendimento);
        $substituicoes = [];

        foreach ($receita->ingredientes as $linha) {
            $id = $linha->ingrediente_id;

            if (! isset($bloqueados[$id])) {
                continue;
            }

            $nome = $ctx->ingredientes[$id]->nome ?? 'ingrediente';
            $quem = implode(', ', array_unique($bloqueados[$id]));

            if ($linha->opcional) {
                $avisos[] = "Sem {$nome} (opcional) por causa de {$quem}.";

                continue;
            }

            $substituto = $this->escolherSubstituto($id, $linha->quantidade * $fator, $bloqueados, $ctx);

            if ($substituto === null) {
                return [
                    'receita' => $receita, 'compativel' => false, 'score' => -1000.0, 'substituicoes' => [],
                    'motivos' => [], 'avisos' => ["Contém {$nome}, que {$quem} não pode comer, e não há substituto."],
                ];
            }

            $substituicoes[$id] = $substituto;
            $motivos[] = "Adaptada: {$nome} → {$ctx->ingredientes[$substituto]->nome} ({$quem}).";
        }

        $necessidades = $receita->necessidades($porcoes, false, $substituicoes);
        $n = max(1, count($necessidades));

        // 2. Componentes (0..1, preferência −1..1).
        $coberto = [];
        $faltantes = [];
        foreach ($necessidades as $id => $quantidade) {
            $tem = $ctx->disponivel($id);
            $coberto[$id] = $quantidade > 0 ? min(1.0, $tem / $quantidade) : 1.0;
            if ($tem + 0.0005 < $quantidade) {
                $faltantes[] = $id;
            }
        }
        $estoque = array_sum($coberto) / $n;
        $compras = (count($necessidades) - array_sum($coberto)) / $n; // fração do que falta comprar

        $urgentes = [];
        $pontosValidade = 0.0;
        foreach ($necessidades as $id => $quantidade) {
            $dias = $ctx->validade[$id] ?? null;
            if ($dias !== null && $ctx->disponivel($id) > 0 && $dias <= 7) {
                $pontosValidade += $dias <= 3 ? 1.0 : 0.5;
                $urgentes[] = [$ctx->ingredientes[$id]->nome ?? '', $dias];
            }
        }
        $validade = min(1.0, $pontosValidade / 2);

        $compartilhados = array_values(array_filter(array_keys($necessidades), fn ($id) => ($ctx->planejado[$id] ?? 0) > 0.0005));
        $compartilhado = count($compartilhados) / $n;

        $preferencia = $this->preferencia($pessoas, array_keys($necessidades));
        $favorito = max(-0.3, min(1.0, ($receita->favorita ? 1.0 : 0.0) + ['alta' => 0.3, 'media' => 0.0, 'baixa' => -0.3][$receita->frequencia ?? 'media'] ?? 0.0));

        $tempo = match (true) {
            $receita->tempo_preparo === null => 0.5,
            $receita->tempo_preparo <= 30 => 1.0,
            $receita->tempo_preparo <= 60 => 0.5,
            default => 0.0,
        };
        $praticidade = ($tempo + ['facil' => 1.0, 'media' => 0.5, 'dificil' => 0.0][$receita->dificuldade ?? 'media'] ?? 0.5) / 2;

        [$repeticao, $diasDesdeUso] = $this->repeticao($receita, $refeicao, $ctx);
        $principal = ContextoPlanejamento::principal($necessidades);
        $variedade = $this->principalRepetido($principal, $refeicao, $ctx) ? 0.0 : 1.0;

        $feedback = $ctx->feedback[$receita->id] ?? 0.0;
        // Aprendizado: receitas que vocês escolhem sobem; as que vocês trocam (sugestão rejeitada) descem.
        $aprendizado = tanh((($receita->escolhas ?? 0) - 1.5 * ($receita->rejeicoes ?? 0)) / 5);

        $p = self::PESOS;
        $score = $estoque * $p['estoque'] + $validade * $p['validade'] + $preferencia * $p['preferencia']
            + $favorito * $p['favorito'] + $variedade * $p['variedade'] + $praticidade * $p['praticidade']
            + $compartilhado * $p['compartilhado'] + $feedback * $p['feedback'] + $aprendizado * $p['aprendizado'] - $repeticao * $p['repeticao'] - $compras * $p['compras'];

        // 3. Explicação.
        $emCasa = array_values(array_filter(array_keys($necessidades), fn ($id) => $coberto[$id] >= 1.0));
        if ($emCasa !== []) {
            $motivos[] = 'Já tem em casa: '.$this->nomes($emCasa, $ctx, 4).'.';
        }
        foreach ($urgentes as [$nome, $dias]) {
            $motivos[] = $dias === 0 ? "{$nome} vence hoje." : "{$nome} vence em {$dias} dia(s).";
        }
        if ($receita->favorita) {
            $motivos[] = 'Favorita da família.';
        }
        if ($preferencia > 0.3) {
            $motivos[] = 'Combina com as preferências de quem vai comer.';
        }
        if ($aprendizado >= 0.4) {
            $motivos[] = 'Vocês costumam escolher esta receita.';
        }
        if ($aprendizado <= -0.4) {
            $avisos[] = 'Vocês costumam trocar esta sugestão por outra.';
        }
        if ($feedback >= 0.4) {
            $motivos[] = 'Vocês gostaram quando fizeram.';
        }
        if ($compartilhados !== []) {
            $motivos[] = 'Aproveita ingredientes de outras refeições da semana: '.$this->nomes($compartilhados, $ctx, 3).'.';
        }
        if ($bloqueados !== [] && $substituicoes === []) {
            $motivos[] = 'Atende às restrições alimentares cadastradas.';
        }
        if ($diasDesdeUso !== null && $repeticao > 0) {
            $avisos[] = $diasDesdeUso === 0 ? 'Repete uma receita de hoje.' : "Usada há {$diasDesdeUso} dia(s).";
        }
        if ($feedback <= -0.4) {
            $avisos[] = in_array($receita->id, $ctx->bloqueadas, true) ? 'Marcada como "não fazer novamente".' : 'Vocês não gostaram muito da última vez.';
        }
        if ($faltantes !== []) {
            $avisos[] = 'Falta comprar: '.$this->nomes($faltantes, $ctx, 4).'.';
        }

        return [
            'receita' => $receita, 'compativel' => true, 'score' => round($score, 2),
            'substituicoes' => $substituicoes, 'motivos' => $motivos, 'avisos' => $avisos,
        ];
    }

    /**
     * Receitas compatíveis com a refeição, da melhor para a pior.
     *
     * @param  Collection<int, Receita>  $receitas  com ingredientes carregados
     * @param  array<int, int>  $excluir  ids de receitas a ignorar
     * @return Collection<int, array<string, mixed>>
     */
    public function ranquear(Collection $receitas, RefeicaoPlanejada $refeicao, ContextoPlanejamento $ctx, array $excluir = []): Collection
    {
        return $receitas
            ->reject(fn (Receita $r) => in_array($r->id, $excluir, true) || in_array($r->id, $ctx->bloqueadas, true))
            ->filter(fn (Receita $r) => $this->serveParaRefeicao($r, $refeicao->tipo_refeicao))
            ->map(fn (Receita $r) => $this->avaliar($r, $refeicao, $ctx))
            ->filter(fn (array $a) => $a['compativel'])
            ->sortByDesc('score')
            ->values();
    }

    private function serveParaRefeicao(Receita $receita, string $tipo): bool
    {
        $compativeis = $receita->refeicoes_compativeis ?? [];

        return $compativeis === [] || in_array($tipo, $compativeis, true);
    }

    /**
     * Melhor substituto: mesma unidade base, sem restrição de quem come, preferindo o que há em estoque.
     *
     * @param  array<int, array<int, string>>  $bloqueados
     */
    private function escolherSubstituto(int $ingredienteId, float $quantidade, array $bloqueados, ContextoPlanejamento $ctx): ?int
    {
        $base = $ctx->ingredientes[$ingredienteId]->unidade_base ?? null;
        $melhor = null;
        $melhorChave = null;

        foreach ($ctx->substituicoes[$ingredienteId] ?? [] as $candidato) {
            $id = $candidato['id'];
            $ingrediente = $ctx->ingredientes[$id] ?? null;

            if ($ingrediente === null || $ingrediente->unidade_base !== $base || isset($bloqueados[$id])) {
                continue;
            }

            $cobertura = $quantidade > 0 ? min(1.0, $ctx->disponivel($id) / $quantidade) : 1.0;
            $chave = [$cobertura, $candidato['compatibilidade']];

            if ($melhorChave === null || $chave > $melhorChave) {
                $melhor = $id;
                $melhorChave = $chave;
            }
        }

        return $melhor;
    }

    /**
     * Média, entre os participantes, do gosto pelos ingredientes (−1..1); restrições "preferência" pesam −0,5.
     *
     * @param  Collection<int, \App\Modules\Cardapio\Models\Pessoa>  $pessoas
     * @param  array<int, int>  $ingredientes
     */
    private function preferencia(Collection $pessoas, array $ingredientes): float
    {
        if ($pessoas->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($pessoas as $pessoa) {
            $niveis = $pessoa->preferencias->whereIn('ingrediente_id', $ingredientes)->pluck('nivel');
            $gosto = $niveis->isEmpty() ? 0.0 : $niveis->avg() / 10;
            $evita = $pessoa->restricoes->where('tipo', 'preferencia')->whereIn('ingrediente_id', $ingredientes)->count() * 0.5;
            $total += max(-1.0, min(1.0, $gosto - $evita));
        }

        return $total / $pessoas->count();
    }

    /**
     * @return array{0: float, 1: ?int} [penalidade 0..1, dias desde o uso mais próximo]
     */
    private function repeticao(Receita $receita, RefeicaoPlanejada $refeicao, ContextoPlanejamento $ctx): array
    {
        $menor = null;

        foreach ($ctx->usos as $uso) {
            if (isset($ctx->ignorados[$uso['refeicao_id'] ?? 0])) {
                continue;
            }

            if ($uso['receita_id'] === $receita->id) {
                $dias = (int) abs($uso['data']->diffInDays($refeicao->data));
                $menor = $menor === null ? $dias : min($menor, $dias);
            }
        }

        return [$menor === null ? 0.0 : max(0.0, 1 - $menor / self::JANELA_REPETICAO), $menor];
    }

    private function principalRepetido(?int $principal, RefeicaoPlanejada $refeicao, ContextoPlanejamento $ctx): bool
    {
        if ($principal === null) {
            return false;
        }

        foreach ($ctx->usos as $uso) {
            if (isset($ctx->ignorados[$uso['refeicao_id'] ?? 0])) {
                continue;
            }

            if ($uso['principal'] === $principal && abs($uso['data']->diffInDays($refeicao->data)) <= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function nomes(array $ids, ContextoPlanejamento $ctx, int $limite): string
    {
        $nomes = array_map(fn ($id) => $ctx->ingredientes[$id]->nome ?? '?', $ids);
        $extra = count($nomes) - $limite;

        return implode(', ', array_slice($nomes, 0, $limite)).($extra > 0 ? " e mais {$extra}" : '');
    }
}
