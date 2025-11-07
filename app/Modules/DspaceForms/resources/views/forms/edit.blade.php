<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Formulário: ') . $form->name }}
            </h2>
            <div class="flex space-x-2">
                {{-- Botão para voltar ao Índice de Formulários --}}
                <a href="{{ route('dspace-forms.forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Voltar à Lista') }}
                    </x-secondary-button>
                </a>

                {{-- Botão para voltar ao Início do Módulo --}}
                <a href="{{ route('dspace-forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-auth-session-status class="mb-4" :status="session('success')"/>
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Informações Básicas') }}
                    </h3>
                    <form method="POST" action="{{ route('dspace-forms.forms.update', $form) }}">
                        @csrf
                        @method('PUT')

                        @include('DspaceForms::forms._form', ['form' => $form])

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Salvar Alterações') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Estrutura do Formulário: Linhas e Campos') }}
                    </h3>

                    <div class="mb-6 flex justify-end">
                        <form method="POST" action="{{ route('dspace-forms.forms.rows.store', $form) }}">
                            @csrf
                            <x-secondary-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                                <i class="fa-solid fa-layer-group mr-2"></i> {{ __('Adicionar Nova Linha') }}
                            </x-secondary-button>
                        </form>
                    </div>

                    @php
                        // Carrega as linhas ordenadas e calcula o min/max order para desabilitar botões
                        $allRows = $form->rows()->with('fields')->orderBy('order')->get();
                        $minRowOrder = $allRows->min('order');
                        $maxRowOrder = $allRows->max('order');
                    @endphp

                    <ul class="space-y-4">
                        @forelse ($allRows as $row)
                            <li class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow-md border-t-4 border-indigo-500">

                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="font-bold text-lg text-gray-800 dark:text-gray-200">
                                        Linha #{{ $row->order }}
                                    </h4>
                                    <div class="flex space-x-2 items-center">

                                        <div class="flex flex-col space-y-1">
                                            {{-- Mover para Cima --}}
                                            <form method="POST"
                                                  action="{{ route('dspace-forms.forms.rows.move', [$form, $row]) }}">
                                                @csrf
                                                <input type="hidden" name="direction" value="up">
                                                <button type="submit" @if($row->order === $minRowOrder) disabled
                                                        @endif class="text-gray-600 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                    <i class="fa-solid fa-chevron-up"></i>
                                                </button>
                                            </form>
                                            {{-- Mover para Baixo --}}
                                            <form method="POST"
                                                  action="{{ route('dspace-forms.forms.rows.move', [$form, $row]) }}">
                                                @csrf
                                                <input type="hidden" name="direction" value="down">
                                                <button type="submit" @if($row->order === $maxRowOrder) disabled
                                                        @endif class="text-gray-600 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <x-secondary-button onclick="openFieldModalForCreation({{ $row->id }})"
                                                            class="bg-green-600 hover:bg-green-700 text-white">
                                            <i class="fa-solid fa-plus-circle mr-2"></i> {{ __('Adicionar Campo') }}
                                        </x-secondary-button>

                                        <form method="POST"
                                              action="{{ route('dspace-forms.forms.rows.destroy', [$form, $row]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit"
                                                             onclick="return confirm('Tem certeza que deseja excluir esta linha e todos os campos dentro dela?')"
                                                             class="text-white bg-red-600 hover:bg-red-700">
                                                <i class="fa-solid fa-trash"></i>
                                            </x-danger-button>
                                        </form>
                                    </div>
                                </div>

                                <div class="pl-4 border-l border-gray-300 dark:border-gray-600">
                                    <h5 class="text-sm font-semibold mb-2 text-gray-600 dark:text-gray-400">Campos nesta
                                        Linha:</h5>
                                    <ul class="space-y-2 min-h-[50px]">
                                        @php
                                            $fields = $row->fields()->orderBy('order')->get();
                                            $minFieldOrder = $fields->min('order');
                                            $maxFieldOrder = $fields->max('order');
                                        @endphp
                                        @forelse ($fields as $field)
                                            <li class="bg-white dark:bg-gray-800 p-3 rounded-md shadow-sm border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                                <div>
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">
                                                        {{ $field->dc_schema }}.{{ $field->dc_element }}{{ $field->dc_qualifier ? '.'.$field->dc_qualifier : '' }}
                                                    </span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400"> ({{ $field->label }})</span>
                                                </div>
                                                <div class="flex space-x-2 items-center">

                                                    <div class="flex flex-col space-y-1">
                                                        {{-- Mover para Cima --}}
                                                        <form method="POST"
                                                              action="{{ route('dspace-forms.forms.rows.fields.move', [$form, $row, $field]) }}">
                                                            @csrf
                                                            <input type="hidden" name="direction" value="up">
                                                            <button type="submit"
                                                                    @if($field->order === $minFieldOrder) disabled
                                                                    @endif class="text-gray-600 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                                <i class="fa-solid fa-chevron-up text-xs"></i>
                                                            </button>
                                                        </form>
                                                        {{-- Mover para Baixo --}}
                                                        <form method="POST"
                                                              action="{{ route('dspace-forms.forms.rows.fields.move', [$form, $row, $field]) }}">
                                                            @csrf
                                                            <input type="hidden" name="direction" value="down">
                                                            <button type="submit"
                                                                    @if($field->order === $maxFieldOrder) disabled
                                                                    @endif class="text-gray-600 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                                <i class="fa-solid fa-chevron-down text-xs"></i>
                                                            </button>
                                                        </form>
                                                    </div>

                                                    {{-- Botão de Edição (Abre o modal) --}}
                                                    {{-- Passamos o objeto do campo como JSON string para o JavaScript --}}
                                                    <x-secondary-button
                                                        onclick="openFieldModalForEdit({{ $row->id }}, {{ $field->id }}, '{{ $field->toJson() }}')"
                                                        class="text-xs py-1">
                                                        <i class="fa-solid fa-pencil"></i> {{ __('Editar') }}
                                                    </x-secondary-button>

                                                    <form method="POST"
                                                          action="{{ route('dspace-forms.forms.rows.fields.destroy', [$form, $row, $field]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button type="submit"
                                                                         onclick="return confirm('Tem certeza que deseja excluir este campo?')"
                                                                         class="text-xs py-1">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </x-danger-button>
                                                    </form>
                                                </div>
                                            </li>
                                        @empty
                                            <p class="text-sm text-gray-500 dark:text-gray-400 p-2 text-center">Nenhum
                                                campo nesta linha.</p>
                                        @endforelse
                                    </ul>
                                </div>
                            </li>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma linha configurada. Use o
                                botão "Adicionar Nova Linha" para começar.</p>
                        @endforelse
                    </ul>

                    <x-modal name="edit-field-modal" :show="false" focusable>
                        <div class="p-6">
                            <h2 id="field-modal-title"
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"></h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Configure os metadados e o tipo de campo DSpace.') }}
                            </p>

                            @include('DspaceForms::forms.fields._modal_form')

                        </div>
                    </x-modal>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>

<script>
    // --- FUNÇÕES DE UTILIDADE E GESTÃO DE MODAL ---

    // Esta função nativa dispara o evento que o componente x-modal (Alpine.js) está ouvindo
    function openModalAlpine(modalName) {
        window.dispatchEvent(new CustomEvent('open-modal', {detail: modalName}));
    }

    // Função para mostrar/esconder o campo de seleção de lista
    window.toggleListSelection = function (inputType) {
        // Tipos de input que requerem uma lista (dropdown, list, lookup)
        const isListType = inputType === 'dropdown' || inputType === 'list' || inputType === 'lookup';

        const listGroup = document.getElementById('list-selection-group');
        const closedGroup = document.getElementById('vocabulary-closed-group');

        if (listGroup) {
            if (isListType) {
                listGroup.style.display = 'block';
                closedGroup.style.display = 'flex'; // Usamos 'flex' para o componente x-modal
            } else {
                listGroup.style.display = 'none';
                closedGroup.style.display = 'none';

                // Limpa o campo de seleção e desmarca o checkbox para evitar envio de dados incorretos
                document.getElementById('list_selection').value = '';
                document.getElementById('vocabulary_closed').checked = false;
            }
        }
    }

    // Função utilitária para preencher o formulário
    function fillFieldForm(fieldData) {
        // Garante que booleanos e nulos sejam tratados corretamente
        fieldData.repeatable = !!fieldData.repeatable;
        fieldData.vocabulary_closed = !!fieldData.vocabulary_closed;

        // NOVO: Calcula o valor da lista combinada
        let combinedListValue = '';
        if (fieldData.vocabulary) {
            combinedListValue = 'detailed:' + fieldData.vocabulary;
        } else if (fieldData.value_pairs_name) {
            combinedListValue = 'simple:' + fieldData.value_pairs_name;
        }

        // Preenchimento de campos de texto e select
        document.getElementById('dc_schema').value = fieldData.dc_schema || 'dc';
        document.getElementById('dc_element').value = fieldData.dc_element || '';
        document.getElementById('dc_qualifier').value = fieldData.dc_qualifier || '';
        document.getElementById('label').value = fieldData.label || '';

        // Define o input_type E aciona a função de visibilidade
        document.getElementById('input_type').value = fieldData.input_type || 'onebox';
        window.toggleListSelection(fieldData.input_type || 'onebox');

        // Define o valor do novo campo consolidado
        document.getElementById('list_selection').value = combinedListValue;

        document.getElementById('hint').value = fieldData.hint || '';
        document.getElementById('required').value = fieldData.required || '';
        document.getElementById('vocabulary').value = fieldData.vocabulary || ''; // Campo antigo, mas ainda deve ser preenchido para segurança

        // Preenchimento de checkboxes
        document.getElementById('repeatable').checked = fieldData.repeatable;
        document.getElementById('vocabulary_closed').checked = fieldData.vocabulary_closed;

        // Oculta/Exibe o campo hidden para garantir o envio de '0' ou '1'
        document.getElementById('repeatable-hidden').name = fieldData.repeatable ? '' : 'repeatable';
        document.getElementById('vocabulary_closed-hidden').name = fieldData.vocabulary_closed ? '' : 'vocabulary_closed';
    }

    // --- FUNÇÕES DE AÇÃO DO MODAL ---

    // As URLs base para o form
    const formId = {{ $form->id }};
    const baseUrl = '/dspace-forms-editor/forms/' + formId + '/rows/';

    // Abre o modal para CRIAÇÃO
    window.openFieldModalForCreation = function (rowId) {
        document.getElementById('field-modal-title').textContent = 'Criar Novo Campo';
        document.getElementById('field-modal-submit-text').textContent = 'Criar Campo';

        // Configura a URL de Ação para POST (Store)
        const actionUrl = baseUrl + rowId + '/fields';
        document.getElementById('field-modal-form').setAttribute('action', actionUrl);
        document.getElementById('_method').value = 'POST';

        // Limpa o formulário (ou preenche com valores padrão de um campo novo)
        fillFieldForm({
            id: null, dc_schema: 'dc', dc_element: '', dc_qualifier: '', repeatable: true,
            label: 'Novo Campo', input_type: 'onebox', hint: '', required: '', vocabulary: '',
            vocabulary_closed: false, value_pairs_name: ''
        });

        openModalAlpine('edit-field-modal');
    }

    // Abre o modal para EDIÇÃO
    window.openFieldModalForEdit = function (rowId, fieldId, fieldDataJson) {
        const fieldData = JSON.parse(fieldDataJson);

        document.getElementById('field-modal-title').textContent = 'Editar Campo: ' + fieldData.label;
        document.getElementById('field-modal-submit-text').textContent = 'Salvar Campo';

        // Configura a URL de Ação para PUT (Update)
        const actionUrl = baseUrl + rowId + '/fields/' + fieldId;
        document.getElementById('field-modal-form').setAttribute('action', actionUrl);
        document.getElementById('_method').value = 'PUT';

        // Preenche o formulário com os dados do campo
        fillFieldForm(fieldData);

        openModalAlpine('edit-field-modal');
    }
</script>
