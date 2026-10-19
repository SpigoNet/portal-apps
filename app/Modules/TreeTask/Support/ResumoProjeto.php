<?php

namespace App\Modules\TreeTask\Support;

use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Support\Collection;

/**
 * Números de um projeto para os cartões e cabeçalhos (progresso, atrasos, fase atual, tempo parado).
 * Espera o projeto com `fases.tarefas.responsavel` carregados.
 */
class ResumoProjeto
{
    /** Dias sem mexer a partir dos quais o projeto é considerado parado. */
    public const DIAS_PARADO = 30;

    /**
     * @return array{total: int, feitas: int, pct: int, atrasadas: int, faseAtual: ?string, diasParado: int, equipe: Collection, situacao: string, rotulo: string, classe: string}
     */
    public static function de(Projeto $projeto): array
    {
        $tarefas = $projeto->fases->flatMap->tarefas;
        $total = $tarefas->count();
        $feitas = $tarefas->where('status', Tarefa::CONCLUIDA)->count();
        $abertas = $tarefas->where('status', '!=', Tarefa::CONCLUIDA);

        $atrasadas = $abertas->filter(
            fn (Tarefa $t) => $t->data_vencimento !== null && $t->data_vencimento->isBefore(now()->startOfDay())
        )->count();

        $faseAtual = $projeto->fases
            ->sortBy('ordem')
            ->first(fn ($fase) => $fase->tarefas->where('status', '!=', Tarefa::CONCLUIDA)->isNotEmpty())
            ?->nome;

        $ultima = $tarefas->max('updated_at');
        $ultima = ($ultima !== null && $ultima->gt($projeto->updated_at)) ? $ultima : $projeto->updated_at;
        $diasParado = $ultima ? (int) $ultima->diffInDays(now()) : 0;

        $concluido = $projeto->status === Tarefa::CONCLUIDA || ($total > 0 && $feitas === $total);

        [$situacao, $rotulo, $classe] = match (true) {
            $concluido => ['concluido', 'Concluído', 'chip-ok'],
            $atrasadas > 0 => ['ativo', $atrasadas === 1 ? '1 atrasada' : "{$atrasadas} atrasadas", 'chip-risco'],
            $diasParado >= self::DIAS_PARADO => ['parado', "Parado há {$diasParado} dias", 'chip-aviso'],
            default => ['ativo', 'No prazo', 'chip-ok'],
        };

        return [
            'total' => $total,
            'feitas' => $feitas,
            'pct' => $total > 0 ? (int) round($feitas / $total * 100) : 0,
            'atrasadas' => $atrasadas,
            'faseAtual' => $faseAtual,
            'diasParado' => $diasParado,
            'equipe' => $tarefas->pluck('responsavel')->filter()->unique('id')->take(3)->values(),
            'situacao' => $situacao,
            'rotulo' => $rotulo,
            'classe' => $classe,
        ];
    }
}
