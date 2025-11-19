<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Fase;
use Illuminate\Http\Request;

class FaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_projeto' => 'required|exists:treetask_projetos,id_projeto',
            'nome' => 'required|string|max:255',
        ]);

        // Define ordem padrão (última + 1) ou 0
        $ultimaOrdem = Fase::where('id_projeto', $validated['id_projeto'])->max('ordem');
        $validated['ordem'] = $ultimaOrdem ? $ultimaOrdem + 1 : 0;

        Fase::create($validated);

        return redirect()->route('treetask.show', $validated['id_projeto'])
            ->with('success', 'Fase adicionada!');
    }

    public function destroy($id)
    {
        $fase = Fase::findOrFail($id);
        $projetoId = $fase->id_projeto;
        $fase->delete();

        return redirect()->route('treetask.show', $projetoId)
            ->with('success', 'Fase removida.');
    }
}
