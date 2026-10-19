<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KanbanController extends Controller
{
    /** Concluídas mais antigas que isso ficam ocultas (a não ser com "todas as concluídas"). */
    private const DIAS_CONCLUIDAS = 14;

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'projeto' => 'nullable|array',
            'projeto.*' => 'integer',
            'responsavel' => 'nullable|integer',
            'prioridade' => 'nullable|string|in:'.implode(',', Tarefa::PRIORIDADES),
            'busca' => 'nullable|string|max:100',
            'atrasadas' => 'nullable|boolean',
            'todas_concluidas' => 'nullable|boolean',
        ]);

        $projetos = Projeto::where('id_user_owner', auth()->id())
            ->orderBy('nome')
            ->get(['id_projeto', 'nome']);

        $query = Tarefa::query()
            ->with(['fase.projeto', 'responsavel'])
            ->whereHas('fase.projeto', fn (Builder $q) => $q->where('id_user_owner', auth()->id()));

        if (! empty($filtros['projeto'])) {
            $query->whereHas('fase', fn (Builder $q) => $q->whereIn('id_projeto', $filtros['projeto']));
        }
        if (! empty($filtros['responsavel'])) {
            $query->where('id_user_responsavel', $filtros['responsavel']);
        }
        if (! empty($filtros['prioridade'])) {
            $query->where('prioridade', $filtros['prioridade']);
        }
        if (! empty($filtros['busca'])) {
            $busca = '%'.mb_strtolower($filtros['busca']).'%';
            $query->where(fn (Builder $q) => $q
                ->whereRaw('LOWER(titulo) LIKE ?', [$busca])
                ->orWhereRaw('LOWER(descricao) LIKE ?', [$busca]));
        }
        if (! empty($filtros['atrasadas'])) {
            $query->where('status', '!=', Tarefa::CONCLUIDA)
                ->whereDate('data_vencimento', '<', now()->toDateString());
        }
        if (empty($filtros['todas_concluidas'])) {
            $query->where(fn (Builder $q) => $q
                ->where('status', '!=', Tarefa::CONCLUIDA)
                ->orWhere('updated_at', '>=', now()->subDays(self::DIAS_CONCLUIDAS)));
        }

        $filtros['projeto'] = array_map('intval', $filtros['projeto'] ?? []);

        $porStatus = $query
            ->orderBy('ordem')
            ->orderByRaw("CASE prioridade WHEN 'Urgente' THEN 4 WHEN 'Alta' THEN 3 WHEN 'Média' THEN 2 ELSE 1 END DESC")
            ->orderByRaw('data_vencimento IS NULL')
            ->orderBy('data_vencimento')
            ->get()
            ->groupBy('status');

        return view('TreeTask::kanban', [
            'colunas' => Tarefa::STATUS,
            'porStatus' => $porStatus,
            'projetos' => $projetos,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'filtros' => $filtros,
            'diasConcluidas' => self::DIAS_CONCLUIDAS,
        ]);
    }

    /**
     * Arrastar um cartão: muda o status e grava a ordem da coluna de destino.
     */
    public function mover(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', Tarefa::STATUS),
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $tarefa = Tarefa::with('fase.projeto')->findOrFail($id);
        abort_unless($tarefa->fase->projeto->id_user_owner === auth()->id(), 403);

        $tarefa->update(['status' => $validated['status']]);

        $donas = Tarefa::whereIn('id_tarefa', $validated['ids'])
            ->whereHas('fase.projeto', fn (Builder $q) => $q->where('id_user_owner', auth()->id()))
            ->pluck('id_tarefa')
            ->all();

        foreach ($validated['ids'] as $posicao => $idTarefa) {
            if (in_array($idTarefa, $donas, true)) {
                Tarefa::where('id_tarefa', $idTarefa)->update(['ordem' => $posicao]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Atalho de "+ Nova tarefa": a tarefa ainda mora numa fase, então usa a primeira do projeto (criando "Geral" se não houver).
     */
    public function novaTarefa(Request $request): RedirectResponse
    {
        $idProjeto = $request->validate(['projeto' => 'required|integer'])['projeto'];

        $projeto = Projeto::findOrFail($idProjeto);
        abort_unless($projeto->id_user_owner === auth()->id(), 403);

        $fase = Fase::where('id_projeto', $projeto->id_projeto)->orderBy('ordem')->first()
            ?? Fase::create(['id_projeto' => $projeto->id_projeto, 'nome' => 'Geral', 'ordem' => 0]);

        return redirect()->route('treetask.tarefas.create', $fase->id_fase);
    }
}
