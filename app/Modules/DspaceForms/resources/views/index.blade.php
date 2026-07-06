<x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editor de Configurações DSpace') }}
            </h2>
            <a href="{{ route('dspace-forms.configurations.create') }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-semibold">
                + Nova Configuração
            </a>
        </div>
    </x-slot>

    @if(isset($stats['config_id']))
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-gear text-indigo-600 dark:text-indigo-400 text-xl"></i>
                            <div>
                                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium uppercase tracking-wider">Configuração Ativa</span>
                                <p class="text-lg font-bold text-indigo-900 dark:text-indigo-100">{{ $config->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 bg-white dark:bg-indigo-900 hover:bg-indigo-100 dark:hover:bg-indigo-800 transition">
                                    <i class="fa-solid fa-arrow-right-arrow-left mr-2"></i>
                                    Trocar
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg border dark:border-gray-700 z-50">
                                    <div class="py-1">
                                        @foreach($allConfigurations as $cfg)
                                            <a href="{{ route('dspace-forms.select-config', $cfg->id) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ $cfg->id === $stats['config_id'] ? 'bg-indigo-50 dark:bg-indigo-900/40 font-semibold' : '' }}">
                                                <div class="flex items-center gap-2">
                                                    @if($cfg->id === $stats['config_id'])
                                                        <i class="fa-solid fa-check text-indigo-600 dark:text-indigo-400 text-xs"></i>
                                                    @endif
                                                    <span>{{ $cfg->name }}</span>
                                                </div>
                                                @if($cfg->description)
                                                    <span class="block text-xs text-gray-500 ml-5">{{ $cfg->description }}</span>
                                                @endif
                                            </a>
                                        @endforeach
                                        <div class="border-t border-gray-200 dark:border-gray-700 mt-1 pt-1">
                                            <a href="{{ route('dspace-forms.configurations.create') }}"
                                               class="block px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                                <i class="fa-solid fa-plus mr-2"></i>Nova Configuração
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('dspace-forms.import.form') }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-yellow-300 dark:border-yellow-600 text-yellow-700 dark:text-yellow-300 bg-white dark:bg-yellow-900 hover:bg-yellow-100 dark:hover:bg-yellow-800 transition">
                                <i class="fa-solid fa-upload mr-2"></i>
                                Importar XML
                            </a>
                            <a href="{{ route('dspace-forms.export.zip') }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 dark:border-green-600 text-green-700 dark:text-green-300 bg-white dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 transition">
                                <i class="fa-solid fa-download mr-2"></i>
                                Exportar ZIP
                            </a>
                        </div>
                    </div>
                </div>

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
                                <a href="{{ route('dspace-forms.forms.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
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
                                <a href="{{ route('dspace-forms.value-pairs.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
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
                                <a href="{{ route('dspace-forms.form-maps.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                    {{ __('Gerenciar Vínculos') }} &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div class="flex items-center">
                                <i class="fa-solid fa-envelope fa-2x text-yellow-500 mr-4"></i>
                                <div>
                                    <h3 class="text-lg font-semibold">{{ __('Templates de E-mail') }}</h3>
                                    <p class="text-2xl font-bold">{{ $stats['email_templates_count'] ?? 0 }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('templates editáveis') }}</p>
                                </div>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="{{ route('dspace-forms.emails.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                    {{ __('Gerenciar Templates') }} &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @if($allConfigurations->isEmpty())
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                        <p class="text-yellow-800 dark:text-yellow-200">Nenhuma configuração encontrada. Crie uma nova configuração para começar.</p>
                    </div>
                @else
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">Selecione uma Configuração</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Escolha uma configuração abaixo para gerenciar seus formulários DSpace.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($allConfigurations as $configItem)
                            <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-lg">{{ $configItem->name }}</h4>
                                        @if($configItem->description)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $configItem->description }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('dspace-forms.select-config', $configItem->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                        Selecionar
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-DspaceForms::layout>
