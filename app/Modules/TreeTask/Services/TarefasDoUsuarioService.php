<?php

namespace App\Modules\TreeTask\Services;

use App\Modules\TreeTask\Models\Tarefa;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tarefas em aberto de um usuário (as dos projetos dele ou as que ele é responsável), para resumos e lembretes.
 */
class TarefasDoUsuarioService
{
    public const CONCLUIDA = 'Concluído';

    /**
     * Vencem hoje.
     *
     * @return Collection<int, Tarefa>
     */
    public function hoje(int $userId, ?Carbon $hoje = null): Collection
    {
        return $this->abertas($userId)
            ->whereDate('data_vencimento', ($hoje ?? now())->toDateString())
            ->get();
    }

    /**
     * Venceram antes de hoje.
     *
     * @return Collection<int, Tarefa>
     */
    public function atrasadas(int $userId, ?Carbon $hoje = null): Collection
    {
        return $this->abertas($userId)
            ->whereDate('data_vencimento', '<', ($hoje ?? now())->toDateString())
            ->get();
    }

    /**
     * Mais prioritárias (prioridade e depois prazo), com ou sem prazo.
     *
     * @return Collection<int, Tarefa>
     */
    public function topPrioridade(int $userId, int $limite = 3): Collection
    {
        return $this->abertas($userId, false)->take($limite)->get();
    }

    /**
     * @return Builder<Tarefa>
     */
    private function abertas(int $userId, bool $comPrazo = true): Builder
    {
        return Tarefa::query()
            ->with('fase.projeto')
            ->where('status', '!=', self::CONCLUIDA)
            ->where(fn (Builder $q) => $q
                ->where('id_user_responsavel', $userId)
                ->orWhereHas('fase.projeto', fn (Builder $p) => $p->where('id_user_owner', $userId)))
            ->when($comPrazo, fn (Builder $q) => $q->whereNotNull('data_vencimento'))
            ->orderByRaw("CASE prioridade WHEN 'Urgente' THEN 4 WHEN 'Alta' THEN 3 WHEN 'Média' THEN 2 WHEN 'Baixa' THEN 1 ELSE 0 END DESC")
            ->orderBy('data_vencimento');
    }
}
