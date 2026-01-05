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
            ->where('status', '!=', 'Concluído') // Otimização: não buscar concluídas desnecessariamente
            ->orderBy('ordem_global', 'asc')
            ->orderBy('prioridade', 'asc')
            ->orderBy('data_vencimento', 'asc')
            ->get();

        // 1. O Hiperfoco (A Única Coisa que Importa)
        // Se houver algo em andamento, o sistema entra em modo "Túnel"
        $focoTotal = $tarefas->first(fn($t) => $t->status === 'Em Andamento');

        // 2. Aguardando Resposta (Bloqueios)
        $aguardando = $tarefas->filter(fn($t) => $t->status === 'Aguardando resposta');

        // 3. Backlog (O resto, que deve ficar oculto se houver foco total)
        $aFazer = $tarefas->filter(function ($t) {
            return $t->status === 'A Fazer' || $t->status === 'Planejamento';
        });

        // 4. Concluídas recentes (apenas para histórico rápido)
        $concluidas = Tarefa::where('id_user_responsavel', $userId)
            ->where('status', 'Concluído')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('TreeTask::focus.index', compact('focoTotal', 'aguardando', 'aFazer', 'concluidas'));
    }
}
