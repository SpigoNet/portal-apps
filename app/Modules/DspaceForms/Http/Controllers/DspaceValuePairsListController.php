<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceFormField;
use App\Modules\DspaceForms\Models\DspaceValuePair;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DspaceValuePairsListController extends Controller
{
    use DspaceConfigSession;

    public function index(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $usedVocabularies = DspaceFormField::whereHas('row.form', function ($q) use ($config) {
            $q->where('xml_configuration_id', $config->id);
        })
            ->where(function ($q) {
                $q->whereNotNull('vocabulary')->orWhereNotNull('value_pairs_name');
            })
            ->get()
            ->flatMap(function ($field) {
                return [$field->vocabulary, $field->value_pairs_name];
            })
            ->filter()
            ->unique()
            ->toArray();

        $allLists = DspaceValuePairsList::where('xml_configuration_id', $config->id)
            ->withCount('pairs')
            ->orderBy('name')
            ->get();

        $usedLists = $allLists->filter(fn ($list) => in_array($list->name, $usedVocabularies));
        $unusedLists = $allLists->reject(fn ($list) => in_array($list->name, $usedVocabularies));

        return view('DspaceForms::value-pairs-index', [
            'usedLists' => $usedLists,
            'unusedLists' => $unusedLists,
            'config' => $config,
        ]);
    }

    public function createList(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255', 'not_in:riccps',
                Rule::unique('dspace_value_pairs_lists')->where('xml_configuration_id', $config->id),
            ],
            'dc_term' => 'nullable|string|max:255',
        ]);

        DspaceValuePairsList::create([
            'name' => $validated['name'],
            'dc_term' => $validated['dc_term'] ?? null,
            'xml_configuration_id' => $config->id,
        ]);

        return redirect()->route('dspace-forms.value-pairs.index')
            ->with('success', "Lista '{$validated['name']}' criada na configuração '{$config->name}'.");
    }

    public function destroyList(DspaceValuePairsList $list)
    {
        if ($list->name === 'riccps') {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', 'A lista "riccps" não pode ser excluída.');
        }

        $isUsedAsVocabulary = DspaceFormField::where('vocabulary', $list->name)->exists();
        $isUsedAsValuePair = DspaceFormField::where('value_pairs_name', $list->name)->exists();

        if ($isUsedAsVocabulary || $isUsedAsValuePair) {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', "A lista '{$list->name}' está em uso em um ou mais formulários e não pode ser excluída.");
        }

        if ($list->pairs()->count() > 0) {
            return redirect()->route('dspace-forms.value-pairs.index')->with('error', "A lista '{$list->name}' não está vazia. Remova todos os {$list->pairs()->count()} itens antes de excluir.");
        }

        $list->delete();

        return redirect()->route('dspace-forms.value-pairs.index')->with('success', 'Lista excluída.');
    }

    public function edit(DspaceValuePairsList $list)
    {
        $pairs = $list->pairs()->orderBy('order')->get();

        return view('DspaceForms::value-pairs-edit', compact('list', 'pairs'));
    }

    public function store(Request $request, DspaceValuePairsList $list)
    {
        $request->validate([
            'displayed_value' => 'required|string|max:512',
            'stored_value' => 'nullable|string|max:512',
        ]);

        $nextOrder = $list->pairs()->max('order') + 1;

        $list->pairs()->create([
            'displayed_value' => $request->displayed_value,
            'stored_value' => $request->stored_value,
            'order' => $nextOrder,
        ]);

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item adicionado com sucesso.');
    }

    public function destroy(DspaceValuePairsList $list, DspaceValuePair $pair)
    {
        if ($pair->list_id !== $list->id) {
            return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('error', 'Item não pertence à lista.');
        }

        $pair->delete();

        $list->pairs()->orderBy('order')->get()->each(function ($p, $index) {
            $p->update(['order' => $index + 1]);
        });

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Item removido e lista reordenada com sucesso.');
    }

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

    public function move(Request $request, DspaceValuePairsList $list, DspaceValuePair $pair)
    {
        $request->validate(['direction' => 'required|in:up,down']);

        $currentOrder = $pair->order;

        if ($request->direction === 'up') {
            $targetPair = $list->pairs()->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first();
        } elseif ($request->direction === 'down') {
            $targetPair = $list->pairs()->where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();
        }

        if ($targetPair) {
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
            ->orderBy('displayed_value', 'asc')
            ->get();

        $order = 1;
        foreach ($pairs as $pair) {
            DB::table('dspace_value_pairs')
                ->where('id', $pair->id)
                ->where('list_id', $list->id)
                ->update(['order' => $order++]);
        }

        return redirect()->route('dspace-forms.value-pairs.edit', $list)->with('success', 'Lista ordenada alfabeticamente com sucesso!');
    }
}
