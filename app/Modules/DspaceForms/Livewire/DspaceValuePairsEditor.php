<?php

namespace App\Modules\DspaceForms\Livewire;

use Livewire\Component;
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceValuePair;

class DspaceValuePairsEditor extends Component
{
    public $lists;
    public $selectedList = null;
    public $pairs = [];

    // Propriedades para o novo item
    public $newDisplayedValue = '';
    public $newStoredValue = '';

    // Propriedades para edição (se precisar de um modal ou edição inline)
    public $editingPairId = null;
    public $editingDisplayedValue = '';
    public $editingStoredValue = '';


    public function mount()
    {
        $this->loadLists();
    }

    public function loadLists()
    {
        // Carrega todas as listas disponíveis
        $this->lists = DspaceValuePairsList::orderBy('name')->get();
    }

    public function selectList($listId)
    {
        $this->selectedList = DspaceValuePairsList::with('pairs')->findOrFail($listId);
        // Garante que os pares estejam ordenados pela coluna 'order' (o relacionamento já faz isso)
        $this->pairs = $this->selectedList->pairs->toArray();
    }

    public function deselectList()
    {
        $this->selectedList = null;
        $this->pairs = [];
        $this->resetNewItemFields();
        $this->resetEditingFields();
    }

    // Adiciona novo item à lista
    public function addPair()
    {
        $this->validate([
            'newDisplayedValue' => 'required|string|max:512',
            'newStoredValue' => 'nullable|string|max:512',
        ]);

        $nextOrder = count($this->pairs);

        $newPair = $this->selectedList->pairs()->create([
            'displayed_value' => $this->newDisplayedValue,
            'stored_value' => $this->newStoredValue,
            'order' => $nextOrder,
        ]);

        // Atualiza a lista de pares na memória
        $this->pairs[] = $newPair->toArray();

        $this->resetNewItemFields();
    }

    // Inicia a edição inline
    public function editPair($pairId)
    {
        $pair = DspaceValuePair::find($pairId);
        if ($pair) {
            $this->editingPairId = $pair->id;
            $this->editingDisplayedValue = $pair->displayed_value;
            $this->editingStoredValue = $pair->stored_value ?? '';
        }
    }

    // Cancela a edição inline
    public function cancelEdit()
    {
        $this->resetEditingFields();
    }

    // Salva as alterações de um item
    public function savePair()
    {
        $this->validate([
            'editingDisplayedValue' => 'required|string|max:512',
            'editingStoredValue' => 'nullable|string|max:512',
        ]);

        $pair = DspaceValuePair::find($this->editingPairId);

        if ($pair) {
            $pair->update([
                'displayed_value' => $this->editingDisplayedValue,
                'stored_value' => $this->editingStoredValue,
            ]);

            // Atualiza o array na memória para refletir a mudança
            $index = array_search($this->editingPairId, array_column($this->pairs, 'id'));
            if ($index !== false) {
                $this->pairs[$index]['displayed_value'] = $this->editingDisplayedValue;
                $this->pairs[$index]['stored_value'] = $this->editingStoredValue;
            }
        }

        $this->resetEditingFields();
    }

    public function removePair($pairId)
    {
        DspaceValuePair::destroy($pairId);
        // Remove do array local
        $this->pairs = array_filter($this->pairs, fn($pair) => $pair['id'] != $pairId);
        // Reindexa as chaves (opcional, mas bom para consistência)
        $this->pairs = array_values($this->pairs);

        // Reordenar todos os itens após a remoção (para manter a ordem limpa)
        $this->reorderPairs();
    }

    public function reorderPairs()
    {
        foreach ($this->pairs as $index => $pairData) {
            // Atualiza a ordem no banco de dados
            DspaceValuePair::where('id', $pairData['id'])->update(['order' => $index]);
            // Atualiza a ordem no array local
            $this->pairs[$index]['order'] = $index;
        }
    }

    public function moveUp($index)
    {
        if ($index > 0) {
            [$this->pairs[$index - 1], $this->pairs[$index]] = [$this->pairs[$index], $this->pairs[$index - 1]];
            $this->reorderPairs();
        }
    }

    public function moveDown($index)
    {
        if ($index < count($this->pairs) - 1) {
            [$this->pairs[$index + 1], $this->pairs[$index]] = [$this->pairs[$index], $this->pairs[$index + 1]];
            $this->reorderPairs();
        }
    }

    private function resetNewItemFields()
    {
        $this->newDisplayedValue = '';
        $this->newStoredValue = '';
    }

    private function resetEditingFields()
    {
        $this->editingPairId = null;
        $this->editingDisplayedValue = '';
        $this->editingStoredValue = '';
    }

    public function render()
    {
        return view('DspaceForms::livewire.dspace-value-pairs-editor');
    }
}
