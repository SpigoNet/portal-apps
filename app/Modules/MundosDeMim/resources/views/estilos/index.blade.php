<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Catálogo de Estilos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-8">
                <p class="text-gray-600">
                    O <strong>Mundos de Mim</strong> seleciona automaticamente um estilo por dia baseado no seu perfil.
                    Abaixo estão os universos que você poderá visitar em breve.
                </p>
            </div>

            @if($sazonais->isNotEmpty())
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-yellow-400 pl-3">Temas Sazonais (Tempo Limitado)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    @foreach($sazonais as $tema)
                        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-6 shadow-sm">
                            <h4 class="font-bold text-lg text-yellow-800">{{ $tema->name }}</h4>
                            <p class="text-sm text-yellow-700 mt-2">
                                Disponível de {{ \Carbon\Carbon::parse($tema->starts_at)->format('d/m') }}
                                até {{ \Carbon\Carbon::parse($tema->ends_at)->format('d/m') }}
                            </p>
                            <span class="inline-block mt-3 px-2 py-1 bg-yellow-200 text-yellow-800 text-xs rounded-full font-bold">
                                Raro
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <h3 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-indigo-500 pl-3">Coleção Permanente</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach($padroes as $tema)
                    <div class="bg-white rounded-lg p-4 shadow border hover:border-indigo-300 transition-colors">
                        <h4 class="font-bold text-gray-700">{{ $tema->name }}</h4>
                        <span class="text-xs text-gray-500 uppercase tracking-wide border rounded px-1 mt-1 inline-block">
                            Rating: {{ $tema->age_rating }}
                        </span>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
