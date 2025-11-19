<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Reordena as Fases de um projeto
     */
    public function reorderFases(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $index => $id) {
            Fase::where('id_fase', $id)->update(['ordem' => $index]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Reordena as Tarefas dentro de uma Fase (Kanban vertical)
     */
    public function reorderTarefas(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $index => $id) {
            Tarefa::where('id_tarefa', $id)->update(['ordem' => $index]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Reordena as Tarefas Globalmente (Modo Foco)
     */
    public function reorderGlobal(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $index => $id) {
            Tarefa::where('id_tarefa', $id)->update(['ordem_global' => $index]);
        }

        return response()->json(['status' => 'success']);
    }
}
