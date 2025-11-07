<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Gerenciar Vínculos (Comunidade/Coleção)') }}
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
                    x-on:click.prevent="$dispatch('open-modal', 'create-map-modal')"
                    class="bg-green-600 hover:bg-green-500 active:bg-green-700"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Criar Novo Vínculo
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
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <strong>Opa!</strong> Havia alguns problemas com seus dados.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Aqui você define qual Processo de Submissão (item-submission.xml) será usado para uma determinada Coleção (via Handle) ou Tipo de Entidade (Entity Type).
                </p>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo de Vínculo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chave (Handle ou Entidade)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Processo de Submissão</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($maps as $map)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $map->map_type === 'handle' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $map->map_type === 'handle' ? 'Handle' : 'Tipo de Entidade' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $map->map_key }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $map->submission_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'edit-map-modal-{{ $map->id }}')"
                                        class="mr-2"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </x-secondary-button>

                                    <x-danger-button
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'delete-map-modal-{{ $map->id }}')"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </x-danger-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Nenhum vínculo (mapa) cadastrado.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de Criação -->
    <x-modal name="create-map-modal" focusable maxWidth="lg">
        <form method="POST" action="{{ route('dspace-forms.form-maps.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Criar Novo Vínculo') }}
            </h2>

            <div class="mt-6 space-y-4">
                <!-- Tipo de Vínculo -->
                <div>
                    <x-input-label for="map_type" value="{{ __('Tipo de Vínculo') }}" />
                    <select id="map_type" name="map_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <option value="handle" @selected(old('map_type') == 'handle')>Handle da Coleção</option>
                        <option value="entity-type" @selected(old('map_type') == 'entity-type')>Tipo de Entidade</option>
                    </select>
                </div>

                <!-- Chave (Handle ou Entidade) -->
                <div>
                    <x-input-label for="map_key" value="{{ __('Chave (Handle ou Tipo de Entidade)') }}" />
                    <x-text-input
                        id="map_key"
                        name="map_key"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ex: 123456789/1 ou Tese"
                        required
                        :value="old('map_key')"
                    />
                </div>

                <!-- Processo de Submissão -->
                <div>
                    <x-input-label for="submission_name" value="{{ __('Processo de Submissão') }}" />
                    <select id="submission_name" name="submission_name" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        @foreach($submission_processes as $name)
                            <option value="{{ $name }}" @selected(old('submission_name') == $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-primary-button class="ms-3 bg-green-600 hover:bg-green-500 active:bg-green-700">
                    {{ __('Criar Vínculo') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modais de Edição e Exclusão (Dentro do Loop) -->
    @foreach ($maps as $map)
        <!-- Modal de Edição -->
        <x-modal name="edit-map-modal-{{ $map->id }}" focusable maxWidth="lg">
            <form method="POST" action="{{ route('dspace-forms.form-maps.update', $map) }}" class="p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Editar Vínculo') }}
                </h2>

                <div class="mt-6 space-y-4">
                    <!-- Tipo de Vínculo -->
                    <div>
                        <x-input-label for="map_type_{{ $map->id }}" value="{{ __('Tipo de Vínculo') }}" />
                        <select id="map_type_{{ $map->id }}" name="map_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="handle" @selected($map->map_type == 'handle')>Handle da Coleção</option>
                            <option value="entity-type" @selected($map->map_type == 'entity-type')>Tipo de Entidade</option>
                        </select>
                    </div>

                    <!-- Chave (Handle ou Entidade) -->
                    <div>
                        <x-input-label for="map_key_{{ $map->id }}" value="{{ __('Chave (Handle ou Tipo de Entidade)') }}" />
                        <x-text-input
                            id="map_key_{{ $map->id }}"
                            name="map_key"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :value="$map->map_key"
                        />
                    </div>

                    <!-- Processo de Submissão -->
                    <div>
                        <x-input-label for="submission_name_{{ $map->id }}" value="{{ __('Processo de Submissão') }}" />
                        <select id="submission_name_{{ $map->id }}" name="submission_name" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @foreach($submission_processes as $name)
                                <option value="{{ $name }}" @selected($map->submission_name == $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancelar') }}
                    </x-secondary-button>

                    <x-primary-button class="ms-3">
                        {{ __('Salvar Alterações') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        <!-- Modal de Exclusão -->
        <x-modal name="delete-map-modal-{{ $map->id }}" focusable maxWidth="md">
            <form method="POST" action="{{ route('dspace-forms.form-maps.destroy', $map) }}" class="p-6">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Excluir Vínculo') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Tem certeza que deseja excluir o vínculo para <strong>{{ $map->map_key }}</strong>?
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancelar') }}
                    </x-secondary-button>
                    <x-danger-button class="ms-3">
                        {{ __('Excluir Vínculo') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @endforeach

</x-app-layout>
