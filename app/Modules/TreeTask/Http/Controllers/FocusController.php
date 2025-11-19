<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;

class FocusController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $tarefas = Tarefa::with(['fase.projeto'])
            ->where('id_user_responsavel', $userId)
            // Ordem: Manual > Urgência > Prioridade (já definida)
            ->orderBy('ordem_global', 'asc')
            ->orderBy('prioridade', 'asc')
            ->orderBy('data_vencimento', 'asc')
            ->get();

        // 1. Em Andamento (FAZENDO AGORA)
        $emAndamento = $tarefas->filter(fn($t) => $t->status === 'Em Andamento');

        // 2. Aguardando Resposta (BLOQUEIO - Prioridade Máxima após o que está em execução)
        $aguardando = $tarefas->filter(fn($t) => $t->status === 'Aguardando resposta');

        // 3. A Fazer (BACKLOG)
        $aFazer = $tarefas->filter(function ($t) {
            return $t->status === 'A Fazer' || $t->status === 'Planejamento';
        });

        // 4. Concluídas
        $concluidas = $tarefas->filter(fn($t) => $t->status === 'Concluído')->take(5);

        // Passa os 4 grupos para a view
        return view('TreeTask::focus.index', compact('emAndamento', 'aguardando', 'aFazer', 'concluidas'));
    }
}
