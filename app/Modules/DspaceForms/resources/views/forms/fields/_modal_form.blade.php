@php
    // Busca todas as listas de Value Pairs para preencher o campo consolidado
    $valueLists = App\Modules\DspaceForms\Models\DspaceValuePairsList::orderBy('name')->pluck('name', 'name');
@endphp

<form id="field-modal-form" method="POST" action="">
    @csrf

    <input type="hidden" name="_method" id="_method" value="POST">

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

        <div>
            <x-input-label for="dc_schema" :value="__('Esquema (dc-schema)')" />
            <select id="dc_schema" name="dc_schema" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                <option value="dc">dc</option>
                <option value="local">local</option>
                <option value="dspace">dspace</option>
            </select>
        </div>

        <div>
            <x-input-label for="dc_element" :value="__('Elemento (dc-element)')" />
            <x-text-input id="dc_element" name="dc_element" type="text" class="mt-1 block w-full" required autocomplete="off" />
        </div>

        <div class="col-span-1">
            <x-input-label for="dc_qualifier" :value="__('Qualificador (dc-qualifier)')" />
            <x-text-input id="dc_qualifier" name="dc_qualifier" type="text" class="mt-1 block w-full" autocomplete="off" />
        </div>

        <div class="col-span-1">
            <x-input-label for="label" :value="__('Label / Título do Campo')" />
            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" required autocomplete="off" />
        </div>

        <div class="col-span-1">
            <x-input-label for="input_type" :value="__('Tipo de Input (input-type)')" />
            <select id="input_type" name="input_type" onchange="toggleListSelection(this.value)" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                <option value="onebox">Caixa de Texto Simples</option>
                <option value="textarea">Área de Texto (Múltiplas Linhas)</option>
                <option value="name">Nome Pessoal (Autor/Colaborador)</option>
                <option value="date">Data</option>
                <option value="dropdown">Dropdown (Lista de Valores - Simples)</option>
                <option value="list">Lista de Escolha (Value Pairs - Simples)</option>
                <option value="lookup">Lookup Box (Busca Externa - Detalhada)</option>
            </select>
        </div>

        <div class="col-span-1 flex items-center pt-6">
            <input id="repeatable" name="repeatable" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" value="1" />
            <x-input-label for="repeatable" class="ml-2" :value="__('Repetível (repeatable)')" />
            <input type="hidden" name="repeatable" value="0" id="repeatable-hidden">
        </div>

        <div class="col-span-2" id="list-selection-group" style="display: none;">
            <x-input-label for="list_selection" :value="__('Lista de Valores ou Vocabulário')" />
            <select id="list_selection" name="list_selection" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="">-- Nenhum/Outro Tipo de Input --</option>

                @foreach ($valueLists as $name)
                    <option value="simple:{{ $name }}">Lista Simples (Value Pairs): {{ $name }}</option>
                    <option value="detailed:{{ $name }}">Vocabulário Detalhado: {{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-span-2 flex items-center" id="vocabulary-closed-group" style="display: none;">
            <input id="vocabulary_closed" name="vocabulary_closed" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" value="1" />
            <x-input-label for="vocabulary_closed" class="ml-2" :value="__('Vocabulário Fechado (vocabulary-closed)')" />
            <input type="hidden" name="vocabulary_closed" value="0" id="vocabulary_closed-hidden">
        </div>

        <div class="col-span-2">
            <x-input-label for="hint" :value="__('Hint / Ajuda')" />
            <x-text-input id="hint" name="hint" type="text" class="mt-1 block w-full" autocomplete="off" />
        </div>

        <div class="col-span-2">
            <x-input-label for="required" :value="__('Required (Mensagem de Erro, ex: Este campo é obrigatório)')" />
            <x-text-input id="required" name="required" type="text" class="mt-1 block w-full" autocomplete="off" />
        </div>

        <input type="hidden" name="value_pairs_name" id="value_pairs_name" value="">
        <input type="hidden" name="vocabulary" id="vocabulary" value="">

    </div>

    <div class="mt-6 flex justify-end">
        <x-secondary-button type="button" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'edit-field-modal' }))">
            {{ __('Cancelar') }}
        </x-secondary-button>

        <x-primary-button class="ml-3" type="submit">
            <span id="field-modal-submit-text">Salvar Campo</span>
        </x-primary-button>
    </div>
</form>
