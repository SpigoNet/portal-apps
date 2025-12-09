<?php
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use Illuminate\Database\Eloquent\Collection;

/** @var Collection|DspaceValuePairsList[] $usedLists */
/** @var Collection|DspaceValuePairsList[] $unusedLists */
?><x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Gerenciar Vocabulários e Listas de Valores') }}
            </h2>
            <div class="flex space-x-2">
                {{-- Botão para voltar ao Início do Módulo --}}
                <a href="{{ route('dspace-forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                    </x-secondary-button>
                </a>

                <x-primary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'create-list')"
                    class="bg-green-600 hover:bg-green-500 active:bg-green-700"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Criar Nova Lista
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Mensagens de Feedback -->
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- GRUPO: LISTAS EM USO -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                    <i class="fa-solid fa-link text-green-500 mr-2"></i> Listas e Vocabulários em Uso ({{ $usedLists->count() }})
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Estas listas são atualmente referenciadas em um ou mais formulários.</p>

                <div class="overflow-x-auto mb-10 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome da Lista</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">DC Term</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Itens</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($usedLists as $list)
                            @php
                                // Verifica se o nome da lista é 'riccps'
                                $isRiccps = $list->name === 'riccps';
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $list->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $list->dc_term ?? 'Vocabulário Puro' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $list->pairs_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($isRiccps)
                                        <a href="https://ric.cps.sp.gov.br/controlledvocabulary/info.jsp" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <i class="fa-solid fa-up-right-from-square mr-2"></i> Edição externa
                                        </a>
                                    @else
                                        <a href="{{ route('dspace-forms.value-pairs.edit', $list) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <i class="fa-solid fa-list-ul mr-2"></i> Selecionar / Editar
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Nenhuma lista de valores ou vocabulário em uso encontrado.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- GRUPO: LISTAS FORA DE USO -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                    <i class="fa-solid fa-trash-can text-red-500 mr-2"></i> Listas e Vocabulários Fora de Uso ({{ $unusedLists->count() }})
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Estas listas não estão sendo usadas por nenhum campo de formulário no momento.</p>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome da Lista</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">DC Term</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Itens</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($unusedLists as $list)
                            @php
                                // Verifica se o nome da lista é 'riccps'
                                $isRiccps = $list->name === 'riccps';
                                // Verifica se a lista está vazia
                                $isEmpty = $list->pairs_count === 0;
                                // Ação de edição (habilitada exceto para riccps)
                                $editButton = view('components.primary-button', [
                                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                                        'href' => route('dspace-forms.value-pairs.edit', $list),
                                        'class' => 'bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 mr-2'
                                    ])
                                ])->with('slot', '<i class="fa-solid fa-list-ul mr-2"></i> Selecionar / Editar')->render();
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $list->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $list->dc_term ?? 'Vocabulário Puro' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $list->pairs_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($isRiccps)
                                        <a href="https://ric.cps.sp.gov.br/controlledvocabulary/info.jsp" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <i class="fa-solid fa-up-right-from-square mr-2"></i> Edição externa
                                        </a>
                                    @else
                                        <a href="{{ route('dspace-forms.value-pairs.edit', $list) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <i class="fa-solid fa-list-ul mr-2"></i> Selecionar / Editar
                                        </a>

                                        @if ($isEmpty)
                                            <!-- Botão de Excluir visível se a lista não estiver em uso e estiver vazia -->
                                            <form action="{{ route('dspace-forms.value-pairs.destroyList', $list) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja EXCLUIR permanentemente a lista \'{{ $list->name }}\'? Esta ação não pode ser desfeita.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                    <i class="fa-solid fa-trash-can mr-2"></i> Excluir Lista
                                                </button>
                                            </form>
                                        @else
                                            <!-- Aviso de que a lista precisa estar vazia para ser excluída -->
                                            <span class="inline-flex items-center px-4 py-2 bg-yellow-200 border border-transparent rounded-md font-semibold text-xs text-yellow-800 uppercase tracking-widest cursor-not-allowed ml-2" title="Remova os {{ $list->pairs_count }} itens para excluir a lista.">
                                                <i class="fa-solid fa-triangle-exclamation mr-2"></i> Excluir (Vazia)
                                            </span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Nenhuma lista de valores ou vocabulário fora de uso encontrado.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de Criação de Lista -->
    <x-modal name="create-list" focusable maxWidth="md">
        <form method="POST" action="{{ route('dspace-forms.value-pairs.storeNewList') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Criar Nova Lista ou Vocabulário') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Use este formulário para criar uma nova lista de valores (form-value-pair) ou um vocabulário (vocabulary).') }}
            </p>

            <div class="mt-6 space-y-4">
                <!-- Nome da Lista -->
                <div>
                    <x-input-label for="new_list_name" value="{{ __('Nome Único da Lista') }}" />
                    <x-text-input
                        id="new_list_name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ex: cursos, titulos_academicos"
                        required
                        :value="old('name')"
                    />
                    @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- DC Term (Opcional) -->
                <div>
                    <x-input-label for="new_list_dc_term" value="{{ __('Termo DC Associado (Opcional)') }}" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Preencha apenas para Listas de Valores (Ex: dc.title.alternative). Deixe vazio para Vocabulários Puros.</p>
                    <x-text-input
                        id="new_list_dc_term"
                        name="dc_term"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ex: dc.type"
                        :value="old('dc_term')"
                    />
                    @error('dc_term') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-primary-button class="ms-3 bg-green-600 hover:bg-green-500 active:bg-green-700">
                    {{ __('Criar Lista') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-DspaceForms::layout>
