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

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Configurações</h3>
            
            @if($allConfigurations->isEmpty())
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <p class="text-yellow-800 dark:text-yellow-200">Nenhuma configuração encontrada. Crie uma nova configuração para começar.</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($allConfigurations as $configItem)
                                @php
                                    $isSelected = isset($stats['config_id']) && $stats['config_id'] == $configItem->id;
                                @endphp
                                <tr class="{{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($isSelected)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200 mr-2">
                                                    <i class="fa-solid fa-check mr-1"></i> Ativa
                                                </span>
                                            @endif
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $configItem->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $configItem->description ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if(!$isSelected)
                                            <a href="{{ route('dspace-forms.select-config', ['configId' => $configItem->id]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                                <i class="fa-solid fa-check mr-1"></i> Selecionar
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('dspace-forms.configurations.duplicate', $configItem->id) }}" class="inline mr-3">
                                            @csrf
                                            <button type="submit" onclick="event.preventDefault(); if(confirm('Duplicar configuração &quot;{{ $configItem->name }}&quot;?')) { this.closest('form').submit(); }" 
                                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200" title="Duplicar">
                                                <i class="fa-solid fa-copy mr-1"></i> Duplicar
                                            </button>
                                        </form>
                                        @if($isSelected)
                                            <a href="{{ route('dspace-forms.export.zip') }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                <i class="fa-solid fa-download mr-1"></i> Exportar ZIP
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!isset($stats['config_id']) && !$allConfigurations->isEmpty())
                <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <p class="text-yellow-800 dark:text-yellow-200 text-sm">Selecione uma configuração acima para começar a editar.</p>
                </div>
            @endif
        </div>
    </div>

    @if(isset($stats['config_id']))
        <div class="py-2">
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
    @endif
</x-DspaceForms::layout>
