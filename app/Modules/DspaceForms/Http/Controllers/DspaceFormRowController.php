<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceFormRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class DspaceFormRowController extends Controller
{
    /**
     * Store a newly created row in the form.
     */
    public function store(DspaceForm $form)
    {
        // Encontra a próxima ordem.
        $nextOrder = $form->rows()->max('order') + 1;

        $form->rows()->create([
            'order' => $nextOrder,
        ]);

        return Redirect::route('dspace-forms.forms.edit', $form)->with('success', 'Linha adicionada com sucesso.');
    }

    /**
     * Remove the specified row from the form.
     */
    public function destroy(DspaceForm $form, DspaceFormRow $row)
    {
        if ($row->form_id !== $form->id) {
            return Redirect::back()->with('error', 'Linha não pertence a este formulário.');
        }

        DB::beginTransaction();
        try {
            $row->fields()->delete(); // Remove os campos filhos
            $row->delete();

            // Reordenar as linhas restantes
            $form->rows()->orderBy('order')->get()->each(function ($r, $index) {
                $r->update(['order' => $index + 1]);
            });

            DB::commit();
            return Redirect::route('dspace-forms.forms.edit', $form)->with('success', 'Linha e campos associados excluídos com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erro ao excluir a linha: ' . $e->getMessage());
        }
    }

    /**
     * Move a row up or down by swapping its order with the adjacent row.
     */
    public function move(DspaceForm $form, DspaceFormRow $row, Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        if ($row->form_id !== $form->id) {
            return Redirect::back()->with('error', 'Linha não pertence a este formulário.');
        }

        $direction = $request->input('direction');

        // Busca a linha adjacente
        $targetRow = DspaceFormRow::where('form_id', $form->id)
            ->where('order', $direction === 'up' ? '<' : '>')
            ->where('order', $direction === 'up' ? $row->order : $row->order)
            ->orderBy('order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($targetRow) {
            DB::beginTransaction();
            try {
                // Troca as ordens
                $newTargetOrder = $row->order;
                $newCurrentOrder = $targetRow->order;

                $row->update(['order' => $newCurrentOrder]);
                $targetRow->update(['order' => $newTargetOrder]);

                DB::commit();
                return Redirect::route('dspace-forms.forms.edit', $form)->with('success', 'Linha movida com sucesso.');
            } catch (\Exception $e) {
                DB::rollBack();
                return Redirect::back()->with('error', 'Erro ao mover a linha: ' . $e->getMessage());
            }
        }

        return Redirect::back();
    }
}
