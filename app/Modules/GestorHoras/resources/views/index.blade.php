<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Meus Contratos e Projetos') }}
            </h2>
            @can('gh.operacional')
                <a href="{{ route('gestor-horas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    + Novo Contrato
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Em Andamento
                </h3>

                @if($ativos->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($ativos as $contrato)
                            @include('GestorHoras::partials.card-contrato', ['contrato' => $contrato, 'inativo' => false])
                        @endforeach
                    </div>
                @else
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center text-gray-500 border border-gray-200 border-dashed">
                        Nenhum contrato ativo no momento.
                    </div>
                @endif
            </div>

            @if($inativos->count() > 0)
                <div class="border-t pt-8">
                    <h3 class="text-lg font-medium text-gray-500 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        Histórico / Encerrados
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 opacity-75 hover:opacity-100 transition-opacity duration-300">
                        @foreach($inativos as $contrato)
                            @include('GestorHoras::partials.card-contrato', ['contrato' => $contrato, 'inativo' => true])
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
