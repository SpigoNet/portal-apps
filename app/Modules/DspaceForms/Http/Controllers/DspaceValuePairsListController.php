<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceValuePair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DspaceValuePairsListController extends Controller
{
    /**
     * Exibe a lista de todos os Vocabulários/Listas de Valores disponíveis para edição.
     */
    public function index()
    {
        // Usa withCount para contar os itens na lista de forma eficiente
        $lists = DspaceValuePairsList::withCount('pairs')->orderBy('name')->get();

        return view('DspaceForms::value-pairs-index', compact('lists'));
    }

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
