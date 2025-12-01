<?php

namespace App\Modules\GestorHoras\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestorHoras\Models\Contrato;
use App\Modules\GestorHoras\Models\Apontamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApontamentoController extends Controller
{
    /**
     * Salva um novo apontamento de horas.
     */
    public function store(Request $request, $contrato_id)
    {
        Gate::authorize('gh.operacional');

        $contrato = Contrato::findOrFail($contrato_id);
        // --- TRAVA DE SEGURANÇA ---
        if ($contrato->status !== 'ativo') {
            return back()->withErrors(['erro' => 'Não é possível lançar horas em um contrato finalizado ou cancelado.']);
        }

        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'data_realizacao' => 'required|date',
            'horas_gastas' => 'required|numeric|min:0.1',
            'gh_contrato_item_id' => 'nullable|exists:gh_contrato_itens,id', // Validação nova
        ]);

        // Verifica se o item pertence mesmo a este contrato (segurança extra)
        if (!empty($validated['gh_contrato_item_id'])) {
            $itemPertence = $contrato->itens()->where('id', $validated['gh_contrato_item_id'])->exists();
            if (!$itemPertence) {
                return back()->withErrors(['gh_contrato_item_id' => 'O item selecionado não pertence a este contrato.']);
            }
        }

        $minutos = $validated['horas_gastas'] * 60;

        $contrato->apontamentos()->create([
            'gh_contrato_item_id' => $validated['gh_contrato_item_id'] ?? null,
            'descricao' => $validated['descricao'],
            'data_realizacao' => $validated['data_realizacao'],
            'minutos_gastos' => $minutos
        ]);

        return redirect()->back()->with('success', 'Horas lançadas com sucesso!');
    }
}
