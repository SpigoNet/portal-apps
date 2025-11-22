<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;

class CelebrationController extends Controller
{
    public function show($id)
    {
        // Busca a tarefa concluída com os relacionamentos necessários
        $tarefa = Tarefa::with('fase.projeto')->findOrFail($id);

        // Busca tarefas da mesma fase (irmãs) que ainda não foram concluídas
        // Ordenadas por prioridade para sugerir o que é mais importante
        $sugestoes = Tarefa::where('id_fase', $tarefa->id_fase)
            ->where('id_tarefa', '!=', $id) // Exclui a própria tarefa
            ->where('status', '!=', 'Concluído')
            ->orderByRaw("FIELD(prioridade, 'Urgente', 'Alta', 'Média', 'Baixa')")
            ->orderBy('ordem', 'asc')
            ->take(10) // Limita a quantidade para não poluir a tela
            ->get();

        return view('TreeTask::celebration.show', compact('tarefa', 'sugestoes'));
    }
}
