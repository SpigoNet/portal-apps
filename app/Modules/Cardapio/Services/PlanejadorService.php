<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\PlanoSemanal;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Support\TipoRefeicao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Planejamento semanal: monta a grade a partir da rotina e registra o consumo no estoque.
 */
class PlanejadorService
{
    public function __construct(
        private readonly EstoqueService $estoque,
        private readonly RecomendacaoService $recomendacao,
    ) {}

    public static function segundaDaSemana(Carbon|string $data): Carbon
    {
        return Carbon::parse($data)->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /**
     * Cria (ou devolve) o plano da semana com uma refeição para cada dia/tipo em que alguém come,
     * segundo a rotina, as ausências e os eventos. Receitas ficam em branco para escolha ou geração.
     */
    public function criarSemana(Casa $casa, Carbon|string $qualquerDiaDaSemana): PlanoSemanal
    {
        $inicio = self::segundaDaSemana($qualquerDiaDaSemana);

        return DB::transaction(function () use ($casa, $inicio): PlanoSemanal {
            $plano = $casa->planos()->firstOrCreate(['inicio_semana' => $inicio->toDateString()]);
            $this->sincronizar($plano, apenasNovas: true);

            return $plano;
        });
    }

    /**
     * Reconcilia a grade da semana com rotina, ausências e eventos. Refeições já feitas nunca mudam.
     * Participantes ajustados à mão são mantidos (só se tira quem está ausente), a menos que $refazerPessoas.
     *
     * @param  bool  $apenasNovas  só cria o que falta (não remove nem altera refeições existentes)
     * @return array{criadas: int, removidas: int, atualizadas: int}
     */
    public function sincronizar(PlanoSemanal $plano, bool $apenasNovas = false, bool $refazerPessoas = false): array
    {
        $casa = $plano->casa;
        $agenda = AgendaSemana::carregar($casa, $plano->inicio_semana, $plano->fimSemana());
        $resultado = ['criadas' => 0, 'removidas' => 0, 'atualizadas' => 0];

        return DB::transaction(function () use ($plano, $casa, $agenda, $apenasNovas, $refazerPessoas, $resultado): array {
            $existentes = $plano->refeicoes()->with('pessoas')->get()
                ->keyBy(fn (RefeicaoPlanejada $r) => $r->data->toDateString().'|'.$r->tipo_refeicao);

            for ($dia = 0; $dia < 7; $dia++) {
                $data = $plano->inicio_semana->copy()->addDays($dia);

                foreach ($casa->refeicoes() as $tipo) {
                    $slot = $agenda->slot($data, $tipo);
                    $refeicao = $existentes->get($data->toDateString().'|'.$tipo);

                    if ($refeicao === null) {
                        if ($slot !== null) {
                            $nova = $plano->refeicoes()->create([
                                'data' => $data->toDateString(),
                                'tipo_refeicao' => $tipo,
                                'qtd_pessoas' => count($slot['pessoas']) + $slot['extras'],
                                'pessoas_extras' => $slot['extras'],
                            ]);
                            $nova->pessoas()->sync($slot['pessoas']);
                            $resultado['criadas']++;
                        }

                        continue;
                    }

                    if ($apenasNovas || $refeicao->consumida()) {
                        continue;
                    }

                    if ($slot === null) {
                        $refeicao->delete();
                        $resultado['removidas']++;

                        continue;
                    }

                    $atuais = $refeicao->pessoas->pluck('id');
                    $ausentes = $agenda->ausentes($data);
                    $desejadas = ($refeicao->pessoas_manual && ! $refazerPessoas)
                        ? $atuais->reject(fn ($id) => $ausentes->contains($id))->values()->all()
                        : $slot['pessoas'];

                    $mudou = $atuais->sort()->values()->all() !== collect($desejadas)->sort()->values()->all()
                        || $refeicao->pessoas_extras !== $slot['extras'];

                    if ($mudou) {
                        $refeicao->pessoas()->sync($desejadas);
                        $refeicao->update([
                            'qtd_pessoas' => count($desejadas) + $slot['extras'],
                            'pessoas_extras' => $slot['extras'],
                            'pessoas_manual' => $refazerPessoas ? false : $refeicao->pessoas_manual,
                        ]);
                        $resultado['atualizadas']++;
                    }
                }
            }

            return $resultado;
        });
    }

    /**
     * Reaplica a agenda nas semanas de planejamento que cobrem o período (depois de criar/remover evento ou ausência).
     */
    public function sincronizarPeriodo(Casa $casa, Carbon $inicio, Carbon $fim): void
    {
        $casa->planos()
            ->whereDate('inicio_semana', '<=', $fim)
            ->whereDate('inicio_semana', '>=', self::segundaDaSemana($inicio))
            ->get()
            ->each(fn (PlanoSemanal $plano) => $this->sincronizar($plano));
    }

    /**
     * Baixa do estoque os ingredientes da receita (sem opcionais) para as pessoas da refeição.
     * Consome só o que existir (estoque nunca negativo). Idempotente.
     *
     * @return bool false se a refeição já estava consumida ou não tem receita
     */
    public function consumir(RefeicaoPlanejada $refeicao): bool
    {
        if ($refeicao->consumida() || $refeicao->receita_id === null) {
            return false;
        }

        return DB::transaction(function () use ($refeicao): bool {
            $casa = $refeicao->plano->casa;
            $refeicao->load('receita.ingredientes');
            $necessidades = $refeicao->necessidades();
            $ingredientes = $casa->ingredientes()->whereIn('id', array_keys($necessidades))->get()->keyBy('id');

            $consumo = [];
            foreach ($necessidades as $ingredienteId => $quantidade) {
                $consumo[$ingredienteId] = $this->estoque->baixar($casa, $ingredientes[$ingredienteId], $quantidade, limitar: true);
            }

            $refeicao->update(['consumida_em' => now()]);

            $casa->historicos()->updateOrCreate(
                ['refeicao_planejada_id' => $refeicao->id],
                [
                    'receita_id' => $refeicao->receita_id,
                    'data' => $refeicao->data,
                    'tipo_refeicao' => $refeicao->tipo_refeicao,
                    'qtd_pessoas' => $refeicao->qtd_pessoas,
                    'consumida_em' => $refeicao->consumida_em,
                    'consumo' => $consumo,
                ]
            );

            return true;
        });
    }

    /**
     * Gera o cardápio: preenche as refeições sem receita, uma a uma em ordem cronológica, sempre com a
     * melhor opção dado o que a semana já usa (estoque virtual, repetição, ingredientes compartilhados).
     * Na semana inteira, termina com a otimização global. A lancheira recebe vários itens, sem repetir
     * nenhum deles no dia anterior ou seguinte.
     *
     * @param  Collection<int, RefeicaoPlanejada>|null  $alvo  restringe a estas refeições (padrão: a semana toda)
     * @param  bool  $limpar  esquece as receitas atuais do alvo e não as repete (regeneração)
     * @return array{preenchidas: int, sem_opcao: int, melhorias: int}
     */
    public function gerar(PlanoSemanal $plano, ?Collection $alvo = null, bool $limpar = false): array
    {
        return DB::transaction(function () use ($plano, $alvo, $limpar): array {
            $semanaInteira = $alvo === null;
            $refeicoes = $this->carregar($plano);

            $ids = $alvo?->pluck('id')->all();
            $alvo = $refeicoes->filter(fn (RefeicaoPlanejada $r) => ! $r->consumida() && ($ids === null || in_array($r->id, $ids, true)));

            $anteriores = [];
            if ($limpar) {
                foreach ($alvo as $refeicao) {
                    $anteriores[$refeicao->id] = $refeicao->receita_id;

                    // Pedir outra para uma única refeição é um "não gostei": a sugestão anterior perde pontos.
                    if ($alvo->count() === 1 && $refeicao->receita_id !== null && $refeicao->origem === 'sugerida') {
                        Receita::whereKey($refeicao->receita_id)->increment('rejeicoes');
                    }

                    $refeicao->itens()->delete();
                    $refeicao->update(['receita_id' => null, 'substituicoes' => null, 'justificativa' => null, 'origem' => null]);
                    $refeicao->setRelation('receita', null);
                    $refeicao->setRelation('itens', collect());
                }
            }

            $receitas = $plano->casa->receitas()->with('ingredientes')->get();
            $contexto = $this->recomendacao->contexto($plano);
            $resultado = ['preenchidas' => 0, 'sem_opcao' => 0, 'melhorias' => 0];

            foreach ($alvo->filter(fn (RefeicaoPlanejada $r) => $r->receita_id === null) as $refeicao) {
                $excluir = array_filter([$anteriores[$refeicao->id] ?? null]);

                if ($refeicao->tipo_refeicao === TipoRefeicao::LANCHEIRA) {
                    $excluir = array_merge($excluir, $this->itensDaLancheiraVizinha($refeicoes, $refeicao));
                }

                $melhor = $this->recomendacao->ranquear($receitas, $refeicao, $contexto, $excluir)->first();

                if ($melhor === null) {
                    $resultado['sem_opcao']++;

                    continue;
                }

                $this->aplicar($refeicao, $melhor, 'sugerida');
                $contexto->registrarRefeicao($refeicao);

                if ($refeicao->tipo_refeicao === TipoRefeicao::LANCHEIRA) {
                    $this->completarLancheira($refeicao, $refeicoes, $receitas, $contexto, $plano->casa->lancheira_itens ?? 3, $excluir);
                }

                $resultado['preenchidas']++;
            }

            if ($semanaInteira && $resultado['preenchidas'] > 0) {
                $resultado['melhorias'] = $this->otimizar($plano)['melhorias'];
            }

            return $resultado;
        });
    }

    /**
     * Escolha manual de receita para uma refeição: aplica adaptações e a justificativa.
     * Trocar uma sugestão por outra receita conta como rejeição da sugestão e escolha da nova (aprendizado).
     *
     * @return array<string, mixed> avaliação (compativel=false quando alguém não pode comer a receita)
     */
    public function escolher(RefeicaoPlanejada $refeicao, ?Receita $receita): array
    {
        if ($receita === null) {
            $refeicao->itens()->delete();
            $refeicao->update(['receita_id' => null, 'substituicoes' => null, 'justificativa' => null, 'origem' => null]);

            return ['compativel' => true, 'avisos' => []];
        }

        if ($refeicao->receita_id !== $receita->id) {
            if ($refeicao->receita_id !== null && $refeicao->origem === 'sugerida') {
                Receita::whereKey($refeicao->receita_id)->increment('rejeicoes');
            }
            $receita->increment('escolhas');
        }

        $refeicao->load(['plano.casa', 'pessoas.restricoes', 'pessoas.preferencias']);
        $receita->load('ingredientes');

        $avaliacao = $this->recomendacao->avaliar($receita, $refeicao, $this->recomendacao->contexto($refeicao->plano, $refeicao));
        $this->aplicar($refeicao, $avaliacao, 'manual');

        return $avaliacao;
    }

    /**
     * Define os itens extras (ex.: fruta e bebida da lancheira) de uma refeição, adaptando cada um às restrições.
     *
     * @param  array<int, int>  $receitaIds
     * @return array<int, string> avisos de itens incompatíveis (que ficam mesmo assim, por ser escolha manual)
     */
    public function definirItens(RefeicaoPlanejada $refeicao, array $receitaIds): array
    {
        $refeicao->load(['plano.casa', 'pessoas.restricoes', 'pessoas.preferencias']);
        $contexto = $this->recomendacao->contexto($refeicao->plano, $refeicao);
        $avisos = [];

        $refeicao->itens()->delete();
        $receitas = $refeicao->plano->casa->receitas()->with('ingredientes')->whereIn('id', array_unique($receitaIds))->get();

        foreach ($receitas as $receita) {
            if ($receita->id === $refeicao->receita_id) {
                continue;
            }

            $avaliacao = $this->recomendacao->avaliar($receita, $refeicao, $contexto);
            $refeicao->itens()->create(['receita_id' => $receita->id, 'substituicoes' => $avaliacao['substituicoes'] ?: null]);

            if (! $avaliacao['compativel']) {
                $avisos = array_merge($avisos, $avaliacao['avisos']);
            }
        }

        $refeicao->unsetRelation('itens');

        return $avisos;
    }

    /**
     * Opções ranqueadas para trocar a receita de uma refeição (a atual fica de fora).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function alternativas(RefeicaoPlanejada $refeicao, int $limite = 5): Collection
    {
        $refeicao->load(['plano.casa', 'pessoas.restricoes', 'pessoas.preferencias']);
        $receitas = $refeicao->plano->casa->receitas()->with('ingredientes')->get();
        $excluir = array_filter([$refeicao->receita_id]);

        if ($refeicao->tipo_refeicao === TipoRefeicao::LANCHEIRA) {
            $excluir = array_merge($excluir, $this->itensDaLancheiraVizinha($this->carregar($refeicao->plano), $refeicao));
        }

        return $this->recomendacao
            ->ranquear($receitas, $refeicao, $this->recomendacao->contexto($refeicao->plano, $refeicao), $excluir)
            ->take($limite);
    }

    /**
     * Pontuação total da semana: soma da pontuação de cada refeição pendente com receita, cada uma
     * avaliada no contexto das demais (estoque compartilhado, repetições, variedade).
     */
    public function pontuacaoSemana(PlanoSemanal $plano): float
    {
        $pendentes = $this->pendentes($this->carregar($plano));

        return $pendentes->isEmpty() ? 0.0 : $this->somar($pendentes, $this->recomendacao->contextoBase($plano));
    }

    /**
     * Otimização global: depois da escolha gulosa, tenta trocar a receita de cada refeição sugerida pelas
     * melhores alternativas e só aceita a troca se a pontuação da SEMANA inteira subir (assim uma escolha
     * boa isoladamente, mas ruim para o conjunto, é corrigida). Escolhas manuais e a lancheira ficam fixas.
     * Para na hora quando passa de $limiteSegundos (o que já foi melhorado é mantido).
     *
     * @return array{melhorias: int, antes: float, depois: float}
     */
    public function otimizar(PlanoSemanal $plano, int $passadas = 3, float $limiteSegundos = 8.0): array
    {
        return DB::transaction(function () use ($plano, $passadas, $limiteSegundos): array {
            $pendentes = $this->pendentes($this->carregar($plano));
            $base = $this->recomendacao->contextoBase($plano);
            $receitas = $plano->casa->receitas()->with('ingredientes')->get();
            $antes = $pendentes->isEmpty() ? 0.0 : $this->somar($pendentes, $base);
            $atual = $antes;
            $melhorias = 0;
            $inicio = microtime(true);

            for ($i = 0; $i < $passadas && $pendentes->isNotEmpty(); $i++) {
                $mudou = false;

                foreach ($pendentes as $refeicao) {
                    if ($refeicao->origem !== 'sugerida' || $refeicao->tipo_refeicao === TipoRefeicao::LANCHEIRA) {
                        continue;
                    }

                    if (microtime(true) - $inicio > $limiteSegundos) {
                        break 2;
                    }

                    $candidatas = $this->recomendacao
                        ->ranquear($receitas, $refeicao, $this->contextoSem($base, $pendentes, $refeicao->id), [$refeicao->receita_id])
                        ->take(5);

                    [$receitaOriginal, $idOriginal, $substituicoesOriginais] = [$refeicao->receita, $refeicao->receita_id, $refeicao->substituicoes];
                    $melhorTotal = $atual;
                    $melhor = null;

                    foreach ($candidatas as $candidata) {
                        $refeicao->setRelation('receita', $candidata['receita']);
                        $refeicao->receita_id = $candidata['receita']->id;
                        $refeicao->substituicoes = $candidata['substituicoes'] ?: null;

                        $total = $this->somar($pendentes, $base);

                        if ($total > $melhorTotal + 0.5) {
                            $melhorTotal = $total;
                            $melhor = $candidata;
                        }
                    }

                    $refeicao->setRelation('receita', $receitaOriginal);
                    $refeicao->receita_id = $idOriginal;
                    $refeicao->substituicoes = $substituicoesOriginais;

                    if ($melhor !== null) {
                        $this->aplicar($refeicao, $melhor, 'sugerida');
                        $atual = $melhorTotal;
                        $mudou = true;
                        $melhorias++;
                    }
                }

                if (! $mudou) {
                    break;
                }
            }

            if ($melhorias > 0) {
                // As justificativas das demais refeições dependiam do contexto antigo: renova.
                foreach ($pendentes->where('origem', 'sugerida') as $refeicao) {
                    $avaliacao = $this->recomendacao->avaliar($refeicao->receita, $refeicao, $this->contextoSem($base, $pendentes, $refeicao->id));

                    if ($avaliacao['compativel']) {
                        $this->aplicar($refeicao, $avaliacao, 'sugerida');
                    }
                }
            }

            return ['melhorias' => $melhorias, 'antes' => $antes, 'depois' => $pendentes->isEmpty() ? 0.0 : $this->somar($pendentes, $base)];
        });
    }

    /**
     * @return Collection<int, RefeicaoPlanejada> da semana, em ordem cronológica, com tudo que o motor precisa
     */
    private function carregar(PlanoSemanal $plano): Collection
    {
        return $plano->refeicoes()->with(['pessoas.restricoes', 'pessoas.preferencias', 'receita.ingredientes', 'itens.receita.ingredientes'])->get()
            ->sortBy(fn (RefeicaoPlanejada $r) => $r->data->toDateString().'|'.array_search($r->tipo_refeicao, array_keys(TipoRefeicao::rotulos())))
            ->values();
    }

    /**
     * @param  Collection<int, RefeicaoPlanejada>  $refeicoes
     * @return Collection<int, RefeicaoPlanejada>
     */
    private function pendentes(Collection $refeicoes): Collection
    {
        return $refeicoes->filter(fn (RefeicaoPlanejada $r) => ! $r->consumida() && $r->receita_id !== null)->values();
    }

    /**
     * Contexto com todas as refeições pendentes menos uma (a que será reavaliada).
     *
     * @param  Collection<int, RefeicaoPlanejada>  $pendentes
     */
    private function contextoSem(ContextoPlanejamento $base, Collection $pendentes, int $ignorarId): ContextoPlanejamento
    {
        $contexto = clone $base;

        foreach ($pendentes as $refeicao) {
            if ($refeicao->id !== $ignorarId) {
                $contexto->registrarRefeicao($refeicao);
            }
        }

        return $contexto;
    }

    /**
     * Pontuação da semana: contexto com todas as refeições construído uma vez; cada refeição é avaliada
     * "retirando" a si mesma dele (em vez de reconstruir o contexto para cada uma).
     *
     * @param  Collection<int, RefeicaoPlanejada>  $pendentes
     */
    private function somar(Collection $pendentes, ContextoPlanejamento $base): float
    {
        $completo = clone $base;
        $necessidades = [];

        foreach ($pendentes as $refeicao) {
            $necessidades[$refeicao->id] = $refeicao->necessidades();
            $completo->registrarRefeicao($refeicao, $necessidades[$refeicao->id]);
        }

        $total = 0.0;

        foreach ($pendentes as $refeicao) {
            $contexto = clone $completo;
            $contexto->retirar($refeicao->id, $necessidades[$refeicao->id]);

            $avaliacao = $this->recomendacao->avaliar($refeicao->receita, $refeicao, $contexto);
            $total += $avaliacao['compativel'] ? $avaliacao['score'] : -1000.0;
        }

        return $total;
    }

    /**
     * Receitas (principal e itens) das lancheiras do dia anterior e seguinte: não se repetem em dias seguidos.
     *
     * @param  Collection<int, RefeicaoPlanejada>  $refeicoes
     * @return array<int, int>
     */
    private function itensDaLancheiraVizinha(Collection $refeicoes, RefeicaoPlanejada $refeicao): array
    {
        $ids = [];

        foreach ($refeicoes as $outra) {
            if ($outra->id !== $refeicao->id && $outra->tipo_refeicao === TipoRefeicao::LANCHEIRA && $outra->data->diffInDays($refeicao->data, true) === 1.0) {
                $ids = array_merge($ids, array_filter([$outra->receita_id]), $outra->itens->pluck('receita_id')->all());
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Completa a lancheira com itens extras até o total configurado na casa (principal + extras).
     *
     * @param  Collection<int, RefeicaoPlanejada>  $refeicoes
     * @param  Collection<int, Receita>  $receitas
     * @param  array<int, int>  $excluir  receitas que não podem entrar (vizinhas e a anterior)
     */
    private function completarLancheira(RefeicaoPlanejada $refeicao, Collection $refeicoes, Collection $receitas, ContextoPlanejamento $contexto, int $total, array $excluir): void
    {
        $escolhidas = [$refeicao->receita_id];

        for ($i = 1; $i < $total; $i++) {
            $melhor = $this->recomendacao->ranquear($receitas, $refeicao, $contexto, array_merge($excluir, $escolhidas))->first();

            if ($melhor === null) {
                return;
            }

            $item = $refeicao->itens()->create(['receita_id' => $melhor['receita']->id, 'substituicoes' => $melhor['substituicoes'] ?: null]);
            $item->setRelation('receita', $melhor['receita']);
            $refeicao->setRelation('itens', $refeicao->itens->push($item));
            $escolhidas[] = $melhor['receita']->id;

            $contexto->registrar($refeicao->data, $melhor['receita']->id, $melhor['receita']->necessidades(max(1, $refeicao->qtd_pessoas), false, $melhor['substituicoes']), $refeicao->id);
        }
    }

    /**
     * @param  array<string, mixed>  $avaliacao
     */
    private function aplicar(RefeicaoPlanejada $refeicao, array $avaliacao, string $origem): void
    {
        $linhas = array_merge($avaliacao['motivos'], array_map(fn (string $a) => '⚠ '.$a, $avaliacao['avisos']));

        $refeicao->update([
            'receita_id' => $avaliacao['receita']->id,
            'substituicoes' => $avaliacao['substituicoes'] ?: null,
            'justificativa' => $linhas === [] ? null : implode("\n", $linhas),
            'origem' => $origem,
        ]);
        $refeicao->setRelation('receita', $avaliacao['receita']);
    }
}
