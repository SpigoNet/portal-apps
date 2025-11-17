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

        // Busca TUDO do usuário
        $tarefas = Tarefa::with(['fase.projeto'])
            ->where('id_user_responsavel', $userId)
            ->orderBy('prioridade', 'asc') // Ordenação secundária
            ->orderBy('data_vencimento', 'asc')
            ->get();

        // 1. Em Andamento (O que estou fazendo AGORA) - Destaque
        $emAndamento = $tarefas->filter(function ($t) {
            return $t->status === 'Em Andamento';
        });

        // 2. A Fazer (Backlog imediato) - Lista compacta
        $aFazer = $tarefas->filter(function ($t) {
            return $t->status === 'A Fazer' || $t->status === 'Planejamento';
        });

        // 3. Concluídas (Histórico recente) - Detalhada
        $concluidas = $tarefas->filter(function ($t) {
            return $t->status === 'Concluído';
        })->take(5); // Limita a 5 últimas para não poluir

        return view('TreeTask::focus.index', compact('emAndamento', 'aFazer', 'concluidas'));
    }
}
