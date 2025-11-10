<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editor de Configurações DSpace') }}
            </h2>
            <div class="flex items-center space-x-4">

                {{-- COMBO BOX para Seleção e Ações --}}
                <form id="config-selector-form" action="{{ route('dspace-forms.index') }}" method="GET" class="inline-flex">
                    <select name="config_id" id="config-selector"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm">

                        <option value="" disabled>{{ __('Configuração Atual:') }}</option>

                        {{-- Opções de Configurações Disponíveis --}}
                        @foreach ($allConfigurations as $availableConfig)
                            <option value="{{ $availableConfig->id }}"
                                    @if ($availableConfig->id == $stats['config_id']) selected @endif>
                                {{ $availableConfig->name }}
                            </option>
                        @endforeach

                        {{-- AÇÕES ADICIONAIS --}}
                        <option value="" disabled>--- AÇÕES ---</option>

                        {{-- Opção 1: Criar Nova Configuração --}}
                        <option value="create">{{ __('Criar Nova Configuração') }}</option>

                        {{-- Opção 2: Duplicar Configuração Atual --}}
                        <option value="duplicate-{{ $stats['config_id'] }}">{{ __('Duplicar Configuração Atual') }}</option>
                    </select>
                </form>

                <a href="{{ route('dspace-forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                    </x-secondary-button>
                </a>

                {{-- Script para lidar com as ações "Criar" e "Duplicar" --}}
                <script>
                    document.getElementById('config-selector').addEventListener('change', function() {
                        const selectedValue = this.value;
                        const currentConfigId = '{{ $stats['config_id'] }}';

                        if (selectedValue === 'create') {
                            // Redireciona para a rota de criação
                            window.location.href = "{{ route('dspace-forms.configurations.create') }}";
                        } else if (selectedValue.startsWith('duplicate-')) {
                            const configId = selectedValue.split('-')[1];

                            // Cria e submete um formulário POST para a duplicação (mais seguro)
                            if (confirm("Tem certeza que deseja duplicar a configuração '{{ $stats['config_name'] }}'?")) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = "{{ route('dspace-forms.configurations.duplicate', ':id') }}".replace(':id', configId);

                                // Adiciona o token CSRF
                                const csrfToken = document.createElement('input');
                                csrfToken.type = 'hidden';
                                csrfToken.name = '_token';
                                csrfToken.value = '{{ csrf_token() }}';
                                form.appendChild(csrfToken);

                                document.body.appendChild(form);
                                form.submit();
                            } else {
                                // Se cancelar, reseta o dropdown para a opção selecionada
                                this.value = currentConfigId;
                            }
                        } else {
                            // Ação padrão (trocar configuração)
                            document.getElementById('config-selector-form').submit();
                        }
                    });
                </script>

                {{-- Botão de Exportação AGORA COM ID DA CONFIG --}}
                <a href="{{ route('dspace-forms.export.zip', $stats['config_id'] ?? 0) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <i class="fa-solid fa-file-zipper mr-2"></i>
                    {{ __('Exportar Configurações (.zip)') }}
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Restante do conteúdo do dashboard (Stats) permanece o mesmo,
         mas com os links atualizados para incluir o config_id --}}

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center">
                            <i class="fa-solid fa-file-alt fa-2x text-indigo-500 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">{{ __('Lista de Formulários') }}</h3>
                                <p class="text-2xl font-bold">{{ $stats['forms_count'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('formulários configurados') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="{{ route('dspace-forms.forms.index', ['config_id' => $stats['config_id']]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                {{ __('Gerenciar Formulários') }} &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center">
                            <i class="fa-solid fa-list-check fa-2x text-green-500 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">{{ __('Vocabulários e Listas') }}</h3>
                                <p class="text-2xl font-bold">{{ $stats['vocabularies_count'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('listas de valores gerenciadas') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="{{ route('dspace-forms.value-pairs.index', ['config_id' => $stats['config_id']]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                {{ __('Gerenciar Vocabulários') }} &rarr;
                            </a>
                        </div>
                    </div>
                </div>


                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center">
                            <i class="fa-solid fa-link fa-2x text-orange-500 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">{{ __('Vínculos Comunidade/Coleção') }}</h3>
                                <p class="text-2xl font-bold">{{ $stats['maps_count'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('vínculos configurados') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="{{ route('dspace-forms.form-maps.index', ['config_id' => $stats['config_id']]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                {{ __('Gerenciar Vínculos') }} &rarr;
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
