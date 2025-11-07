<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceFormRow;
use App\Modules\DspaceForms\Models\DspaceFormField;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class DspaceFormFieldController extends Controller
{
    private function validationRules(): array
    {
        return [
            'dc_schema' => 'required|string|max:10',
            'dc_element' => 'required|string|max:255',
            'dc_qualifier' => 'nullable|string|max:255',
            'repeatable' => 'nullable|boolean',
            'label' => 'required|string|max:255',
            'input_type' => 'required|string|max:50',
            'hint' => 'nullable|string|max:255',
            'required' => 'nullable|string|max:255', // Mensagem de erro
            'vocabulary' => 'nullable|string|max:255',
            'vocabulary_closed' => 'nullable|boolean',
            'value_pairs_name' => 'nullable|string|max:255|exists:dspace_value_pairs_lists,name',
        ];
    }

    /**
     * Store a newly created field in the row.
     */
    public function store(DspaceForm $form, DspaceFormRow $row, Request $request)
    {
        $validated = $request->validate($this->validationRules());

        $nextOrder = $row->fields()->max('order') + 1;

        // 1. Recebe a lista combinada e a flag de fechamento de vocabulário
        $listSelection = $request->input('list_selection');
        $vocabularyClosed = $request->boolean('vocabulary_closed');

// 2. Reseta os campos antigos (garantir que apenas um seja preenchido)
        $validated['value_pairs_name'] = null;
        $validated['vocabulary'] = null;
        $validated['vocabulary_closed'] = false;

// 3. Processa a nova seleção
        if (!empty($listSelection)) {
            [$type, $name] = explode(':', $listSelection, 2);

            if ($type === 'simple') {
                $validated['value_pairs_name'] = $name;
            } elseif ($type === 'detailed') {
                $validated['vocabulary'] = $name;
                // O campo vocabulary_closed só faz sentido para vocabulários detalhados
                $validated['vocabulary_closed'] = $vocabularyClosed;
            }
        }

        $row->fields()->create(array_merge($validated, [
            'order' => $nextOrder,
        ]));

        // Retorna JSON para a submissão via AJAX
        return response()->json(['success' => true, 'message' => 'Campo criado com sucesso.'], 201);
    }

    /**
     * Update the specified field.
     */
    public function update(DspaceForm $form, DspaceFormRow $row, DspaceFormField $field, Request $request)
    {
        // Sanity check
        if ($field->row_id !== $row->id || $row->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo inválido.'], 403);
        }

        $validated = $request->validate($this->validationRules());

        // 1. Recebe a lista combinada e a flag de fechamento de vocabulário
        $listSelection = $request->input('list_selection');
        $vocabularyClosed = $request->boolean('vocabulary_closed');

// 2. Reseta os campos antigos (garantir que apenas um seja preenchido)
        $validated['value_pairs_name'] = null;
        $validated['vocabulary'] = null;
        $validated['vocabulary_closed'] = false;

// 3. Processa a nova seleção
        if (!empty($listSelection)) {
            [$type, $name] = explode(':', $listSelection, 2);

            if ($type === 'simple') {
                $validated['value_pairs_name'] = $name;
            } elseif ($type === 'detailed') {
                $validated['vocabulary'] = $name;
                // O campo vocabulary_closed só faz sentido para vocabulários detalhados
                $validated['vocabulary_closed'] = $vocabularyClosed;
            }
        }

        $field->update($validated);

        // Retorna JSON para a submissão via AJAX
        return response()->json(['success' => true, 'message' => 'Campo atualizado com sucesso.'], 200);
    }

    /**
     * Remove the specified field.
     */
    public function destroy(DspaceForm $form, DspaceFormRow $row, DspaceFormField $field)
    {
        // Sanity check
        if ($field->row_id !== $row->id || $row->form_id !== $form->id) {
            return Redirect::back()->with('error', 'Campo inválido.');
        }

        $field->delete();

        // Reordenar os campos restantes na linha
        $row->fields()->orderBy('order')->get()->each(function ($f, $index) {
            $f->update(['order' => $index + 1]);
        });


        return Redirect::route('dspace-forms.forms.edit', $form)->with('success', 'Campo excluído com sucesso.');
    }

    /**
     * Move a field up or down by swapping its order with the adjacent field within the same row.
     */
    public function move(DspaceForm $form, DspaceFormRow $row, DspaceFormField $field, Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        if ($field->row_id !== $row->id || $row->form_id !== $form->id) {
            return Redirect::back()->with('error', 'Campo inválido.');
        }

        $direction = $request->input('direction');

        // Busca o campo adjacente
        $targetField = DspaceFormField::where('row_id', $row->id)
            ->where('order', $direction === 'up' ? '<' : '>')
            ->where('order', $direction === 'up' ? $field->order : $field->order)
            ->orderBy('order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($targetField) {
            DB::beginTransaction();
            try {
                // Troca as ordens
                $newTargetOrder = $field->order;
                $newCurrentOrder = $targetField->order;

                $field->update(['order' => $newCurrentOrder]);
                $targetField->update(['order' => $newTargetOrder]);

                DB::commit();
                return Redirect::route('dspace-forms.forms.edit', $form)->with('success', 'Campo movido com sucesso.');
            } catch (\Exception $e) {
                DB::rollBack();
                return Redirect::back()->with('error', 'Erro ao mover o campo: ' . $e->getMessage());
            }
        }

        return Redirect::back();
    }

    /**
     * Retorna dados de configuração para o formulário de campo (AJAX).
     */
    public function getFieldData()
    {
        $inputTypes = [
            'onebox' => 'Caixa de Texto Simples', 'textarea' => 'Área de Texto (Múltiplas Linhas)',
            'name' => 'Nome Pessoal (Autor/Colaborador)', 'date' => 'Data',
            'dropdown' => 'Dropdown (Lista de Valores)', 'list' => 'Lista de Escolha (Value Pairs)',
            'lookup' => 'Lookup Box (Busca Externa)', // E outros...
        ];

        $valueLists = DspaceValuePairsList::orderBy('name')->pluck('name', 'name');

        return response()->json([
            'dcSchemas' => ['dc', 'local', 'dspace', 'dc-legacy'],
            'inputTypes' => $inputTypes,
            'valueLists' => $valueLists->all(),
        ]);
    }
}
