<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceFormField;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceValuePair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DspaceValuePairsListController extends Controller
{
    public function index(Request $request)
    {
        $configId = $request->query('config_id');
        if (!$configId) {
            return redirect()->route('dspace-forms.index')->with('error', 'Configuração não especificada.');
        }

        $currentConfig = DspaceXmlConfiguration::findOrFail($configId);
        if ($currentConfig->user_id !== auth()->id()) abort(403);

        // 1. Obtém nomes de vocabulários usados APENAS nos formulários DESTA configuração
        $usedVocabularies = DspaceFormField::whereHas('row.form', function($q) use ($configId) {
            $q->where('xml_configuration_id', $configId);
        })
            ->where(function($q) {
                $q->whereNotNull('vocabulary')->orWhereNotNull('value_pairs_name');
            })
            ->get()
            ->flatMap(function($field) {
                return [$field->vocabulary, $field->value_pairs_name];
            })
            ->filter()
            ->unique()
            ->toArray();

        // 2. Busca todas as listas PERTENCENTES a esta configuração
        $allLists = DspaceValuePairsList::where('xml_configuration_id', $configId)
            ->withCount('pairs')
            ->orderBy('name')
            ->get();

        // 3. Separação
        $usedLists = $allLists->filter(fn($list) => in_array($list->name, $usedVocabularies));
        $unusedLists = $allLists->reject(fn($list) => in_array($list->name, $usedVocabularies));

        return view('DspaceForms::value-pairs-index', [
            'usedLists' => $usedLists,
            'unusedLists' => $unusedLists,
            'currentConfig' => $currentConfig, // Passa a config para a View
        ]);
    }

    public function createList(Request $request)
    {
        $configId = $request->input('xml_configuration_id');

        $validated = $request->validate([
            'xml_configuration_id' => 'required|exists:dspace_xml_configurations,id',
            'name' => [
                'required', 'string', 'max:255', 'not_in:riccps',
                // Único por configuração
                Rule::unique('dspace_value_pairs_lists')->where('xml_configuration_id', $configId)
            ],
            'dc_term' => 'nullable|string|max:255',
        ]);

        // Verifica propriedade da config
        $config = DspaceXmlConfiguration::findOrFail($configId);
        if($config->user_id !== auth()->id()) abort(403);

        DspaceValuePairsList::create($validated);

        return redirect()->route('dspace-forms.value-pairs.index', ['config_id' => $configId])
            ->with('success', "Lista '{$validated['name']}' criada na configuração '{$config->name}'.");
    }

    /**
     * Remove uma lista de valores (apenas se não estiver em uso e estiver vazia).
     */
    public function destroyList(DspaceValuePairsList $list)
    {
        $configId = $list->xml_configuration_id;
        // 1. Não permite a exclusão da lista 'riccps'
        if ($list->name === 'riccps') {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', 'A lista "riccps" não pode ser excluída.');
        }

        // 2. Verifica se a lista está em uso
        $isUsedAsVocabulary = DspaceFormField::where('vocabulary', $list->name)->exists();
        $isUsedAsValuePair = DspaceFormField::where('value_pairs_name', $list->name)->exists();

        if ($isUsedAsVocabulary || $isUsedAsValuePair) {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', "A lista '{$list->name}' está em uso em um ou mais formulários e não pode ser excluída.");
        }

        // 3. Verifica se a lista está vazia (pairs_count deve ser 0)
        if ($list->pairs()->count() > 0) {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', "A lista '{$list->name}' não está vazia. Remova todos os {$list->pairs()->count()} itens antes de excluir.");
        }

        $list->delete();

        return redirect()->route('dspace-forms.value-pairs.index', ['config_id' => $configId])->with('success', 'Lista excluída.');    }

    /**
     * Exibe o formulário para editar os itens de um Vocabulário/Lista de Valores.
     */
    public function edit(DspaceValuePairsList $list)
    {
        // Carrega os itens ordenados pela coluna 'order'
        $pairs = $list->pairs()->orderBy('order')->get();
        //dd($pairs->toArray());
        return view('DspaceForms::value-pairs-edit', compact('list', 'pairs'));
    }

    /**
     * Adiciona um novo item (pair) a uma lista.
     */
    public function store(Request $request, DspaceValuePairsList $list)
    {
        $request->validate([
            'displayed_value' => 'required|string|max:512',
            'stored_value' => 'nullable|string|max:512',
        ]);

        // Determina a próxima ordem
        $nextOrder = $list->pairs()->max('order') + 1;

        $list->pairs()->create([
            'displayed_value' => $request->displayed_value,
            'stored_value' => $request->stored_value,
            'order' => $nextOrder,
        ]);

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item adicionado com sucesso.');
    }

    /**
     * Remove um item (pair) e reordena os itens restantes.
     */
    public function destroy(DspaceValuePairsList $list, DspaceValuePair $pair)
    {
        // Garante que o item pertence à lista correta
        if ($pair->list_id !== $list->id) {
            return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('error', 'Item não pertence à lista.');
        }

        $pair->delete();

        // Reordenar os itens restantes
        $list->pairs()->orderBy('order')->get()->each(function ($p, $index) {
            $p->update(['order' => $index + 1]);
        });

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item removido e lista reordenada com sucesso.');
    }

    /**
     * Edita o item (pair) - Usado para edição inline via PUT
     */
    public function update(Request $request, DspaceValuePairsList $list, DspaceValuePair $pair)
    {
        $request->validate([
            'displayed_value' => 'required|string|max:512',
            'stored_value' => 'nullable|string|max:512',
        ]);

        if ($pair->list_id !== $list->id) {
            return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('error', 'Item não pertence à lista.');
        }

        $pair->update([
            'displayed_value' => $request->displayed_value,
            'stored_value' => $request->stored_value,
        ]);

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item atualizado com sucesso.');
    }

    /**
     * Move um item para cima ou para baixo e reordena.
     */
    public function move(Request $request, DspaceValuePairsList $list, DspaceValuePair $pair)
    {
        $request->validate(['direction' => 'required|in:up,down']);

        $currentOrder = $pair->order;
        $pairs = $list->pairs()->orderBy('order')->get();
        $targetPair = null;

        if ($request->direction === 'up') {
            // Busca o item imediatamente anterior
            $targetPair = $list->pairs()->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first();
        } elseif ($request->direction === 'down') {
            // Busca o item imediatamente posterior
            $targetPair = $list->pairs()->where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();
        }

        if ($targetPair) {
            // Troca as ordens
            DB::transaction(function () use ($pair, $targetPair) {
                $originalOrder = $pair->order;
                $targetOrder = $targetPair->order;

                $pair->update(['order' => $targetOrder]);
                $targetPair->update(['order' => $originalOrder]);
            });

            return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item movido com sucesso.');
        }

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('error', 'Não foi possível mover o item.');
    }

    public function sortAlphabetical(DspaceValuePairsList $list)
    {
        $pairs = DspaceValuePair::query()
            ->where('list_id', $list->id)
            ->orderBy('displayed_value', 'asc') // Ordena pela exibição
            ->get();


        $order = 1;
        //limpa os order pra NULL

        foreach ($pairs as $pair) {
            // Usando Eloquent puro, mais limpo:
            DB::table('dspace_value_pairs')
                ->where('id', $pair->id)
                ->where('list_id', $list->id)
                ->update(['order' => $order++]);
        }


        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Lista ' . $list->id . ' ordenada alfabeticamente com sucesso! ' . $order);
    }
}
