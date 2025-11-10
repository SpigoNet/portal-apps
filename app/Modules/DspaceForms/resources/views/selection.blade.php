<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Seleção de Configuração DSpace XML') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold">{{ __('Selecione uma Configuração para Gerenciar') }}</h3>
                        <a href="{{ route('dspace-forms.configurations.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Criar Nova Configuração') }}
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mt-6">
                        @if ($allConfigurations->isEmpty())
                            <p class="text-center text-gray-500">{{ __('Nenhuma configuração encontrada. Crie uma para começar.') }}</p>
                        @else
                            <div class="space-y-4">
                                @foreach ($allConfigurations as $config)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md flex justify-between items-center">
                                        <div>
                                            <p class="text-lg font-bold">{{ $config->name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $config->description ?? 'Nenhuma descrição fornecida.' }}</p>
                                        </div>
                                        <div class="flex space-x-3">
                                            {{-- Botão Gerenciar (Selecionar) -> Redireciona para o dashboard filtrado --}}
                                            <a href="{{ route('dspace-forms.index', ['config_id' => $config->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 font-semibold text-sm">
                                                {{ __('Gerenciar') }}
                                            </a>

                                            {{-- Botão Duplicar (via POST form) --}}
                                            <form action="{{ route('dspace-forms.configurations.duplicate', $config) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja duplicar a configuração \'{{ $config->name }}\'?')">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 font-semibold text-sm">
                                                    {{ __('Duplicar') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
