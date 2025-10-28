<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editando Lista: ') . $list->name }}
            </h2>
            <a href="{{ route('dspace-forms.value-pairs.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-400 active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Voltar para Listas
            </a>
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

            <!-- Área de Ações e Informações -->
            <div class="flex justify-between items-center mb-6 p-4 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                <!-- Informações da Lista -->
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">DC Term:</span> {{ $list->dc_term ?? 'N/A' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">Total de Itens:</span> {{ $pairs->count() }}
                    </p>
                </div>

                <!-- Botão de Ordenação Alfabética -->
                <form action="{{ route('dspace-forms.value-pairs.sort.alphabetical', $list) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja ordenar esta lista alfabeticamente? Isso reescreverá a ordem atual.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 active:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <i class="fa-solid fa-sort-alpha-down mr-2"></i> Ordenar Alfabeticamente
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6">

                <!-- Formulário de Adição de Novo Item -->
                <form action="{{ route('dspace-forms.value-pairs.store', $list) }}" method="POST" class="mb-8 p-4 border border-indigo-300 dark:border-indigo-700 rounded-lg bg-indigo-50 dark:bg-gray-700 shadow-md">
                    @csrf
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 text-lg">Adicionar Novo Item à Lista</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                        <!-- Valor Exibido -->
                        <div>
                            <x-input-label for="displayed_value" value="Valor Exibido (Displayed Value)" />
                            <x-text-input name="displayed_value" id="displayed_value" type="text" class="mt-1 block w-full bg-white/80" placeholder="Ex: Ciência da Computação" required value="{{ old('displayed_value') }}" />
                            @error('displayed_value') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Valor Armazenado (Opcional) -->
                        <div>
                            <x-input-label for="stored_value" value="Valor Armazenado (Stored Value - Opcional)" />
                            <x-text-input name="stored_value" id="stored_value" type="text" class="mt-1 block w-full bg-white/80" placeholder="Ex: compsci" value="{{ old('stored_value') }}" />
                            @error('stored_value') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Botão de Adicionar -->
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <i class="fa-solid fa-plus mr-2"></i> Adicionar Item
                        </button>
                    </div>
                </form>

                <!-- Tabela de Itens da Lista -->
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 text-lg">Itens da Lista</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/12">Ordem</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-4/12">Valor Exibido</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-4/12">Valor Armazenado</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-3/12">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($pairs as $pair)
                            <tr
                                x-data="{ isEditing: false,
                                              displayed: '{{ $pair->displayed_value }}',
                                              stored: '{{ $pair->stored_value ?? '' }}' }"
                                id="pair-{{ $pair->id }}"
                            >
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $pair->order }}
                                </td>

                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <span x-show="!isEditing">{{ $pair->displayed_value }}</span>
                                    <form x-show="isEditing" method="POST" action="{{ route('dspace-forms.value-pairs.update', ['list' => $list, 'pair' => $pair]) }}">
                                        @csrf
                                        @method('PUT')
                                        <x-text-input x-model="displayed" name="displayed_value" type="text" class="w-full text-sm"/>
                                    </form>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <span x-show="!isEditing">{{ $pair->stored_value ?? '-' }}</span>
                                    <form x-show="isEditing" method="POST" action="{{ route('dspace-forms.value-pairs.update', ['list' => $list, 'pair' => $pair]) }}">
                                        @csrf
                                        @method('PUT')
                                        <x-text-input x-model="stored" name="stored_value" type="text" class="w-full text-sm"/>
                                    </form>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <!-- Botões de Ordem -->
                                    <form method="POST" action="{{ route('dspace-forms.value-pairs.move', ['list' => $list, 'pair' => $pair]) }}" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" @disabled($pair->order === $pairs->min('order')) class="text-gray-400 disabled:opacity-30 hover:text-gray-600 mx-1 transition-colors p-1" title="Mover para Cima">
                                            <i class="fa-solid fa-arrow-up"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('dspace-forms.value-pairs.move', ['list' => $list, 'pair' => $pair]) }}" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" @disabled($pair->order === $pairs->max('order')) class="text-gray-400 disabled:opacity-30 hover:text-gray-600 mx-1 transition-colors p-1" title="Mover para Baixo">
                                            <i class="fa-solid fa-arrow-down"></i>
                                        </button>
                                    </form>

                                    <!-- Botões de Edição/Salvar/Remover -->
                                    <button x-show="!isEditing" @click="isEditing = true" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mx-1 p-1">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </button>

                                    <button x-show="isEditing" @click="$root.querySelector('#pair-{{ $pair->id }} form').submit()" class="text-green-600 hover:text-green-900 mx-1 p-1">
                                        <i class="fa-solid fa-check"></i> Salvar
                                    </button>

                                    <button x-show="isEditing" @click="isEditing = false" class="text-gray-500 hover:text-gray-700 mx-1 p-1">
                                        <i class="fa-solid fa-xmark"></i> Cancelar
                                    </button>

                                    <form action="{{ route('dspace-forms.value-pairs.destroy', ['list' => $list, 'pair' => $pair]) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Tem certeza que deseja remover este item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1">
                                            <i class="fa-solid fa-trash"></i> Remover
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Nenhum item nesta lista. Adicione um acima!
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
