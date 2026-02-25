<x-ANT::layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Materiais de Aula
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $materia->nome }} — {{ $semestreAtual }}</p>
            </div>
            @if($ehProfessor)
                <a href="{{ route('ant.materiais.create', $materia->id) }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Material
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if($materiaisAgrupados->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-400 italic">Nenhum material publicado para esta disciplina ainda.</p>
                    @if($ehProfessor)
                        <a href="{{ route('ant.materiais.create', $materia->id) }}"
                            class="mt-4 inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            Publicar o primeiro material →
                        </a>
                    @endif
                </div>
            @else
                @foreach($materiaisAgrupados as $dataAula => $itens)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex items-center">
                            <svg class="w-4 h-4 text-indigo-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="font-bold text-gray-700">
                                Aula de {{ \Carbon\Carbon::parse($dataAula)->translatedFormat('l, d \d\e F \d\e Y') }}
                            </span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($itens as $material)
                                @php
                                    $arquivos = json_decode($material->arquivos, true) ?? [];
                                @endphp
                                <div class="px-6 py-4">
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-800">{{ $material->titulo }}</h4>
                                            @if($material->descricao)
                                                <div class="prose prose-sm max-w-none mt-1 text-gray-600">
                                                    {!! $material->descricao !!}
                                                </div>
                                            @endif

                                            @if($arquivos)
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach($arquivos as $caminho)
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($caminho) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200 hover:bg-indigo-100 transition">
                                                        <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        {{ basename($caminho) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>

                                        @if($ehProfessor)
                                            <form method="POST" action="{{ route('ant.materiais.destroy', $material->id) }}"
                                                class="flex-shrink-0"
                                                onsubmit="return confirm('Remover este material e seus arquivos?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Remover material"
                                                    class="text-red-400 hover:text-red-600 transition p-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="mt-2 text-xs text-gray-400">
                                        Por {{ $material->professor->name ?? 'Professor' }}
                                        — Publicado em {{ $material->created_at->format('d/m/Y \à\s H:i') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="text-center pt-2">
                <a href="{{ route('ant.home') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Voltar para Minhas Aulas
                </a>
            </div>

        </div>
    </div>
</x-ANT::layout>
