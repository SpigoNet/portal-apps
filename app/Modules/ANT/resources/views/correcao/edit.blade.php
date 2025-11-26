<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Corrigindo: {{ $entrega->aluno->nome }}
            </h2>
            <a href="{{ route('ant.home') }}" class="text-sm text-gray-500 hover:text-gray-900">Voltar</a>
        </div>
    </x-slot>

    <div class="flex h-screen" style="height: calc(100vh - 65px);"> <div class="flex-1 bg-gray-200 overflow-hidden flex flex-col relative">

            <div class="bg-gray-100 border-b border-gray-300 flex overflow-x-auto">
                @foreach($arquivos as $idx => $arq)
                    <a href="{{ route('ant.correcao.edit', ['idEntrega' => $entrega->id, 'fileIndex' => $idx]) }}"
                       class="px-4 py-2 text-sm font-medium border-r border-gray-300 hover:bg-white whitespace-nowrap {{ $idx == $fileIndex ? 'bg-white text-indigo-600 border-t-2 border-t-indigo-600' : 'text-gray-500' }}">
                        Arquivo {{ $idx + 1 }} (.{{ pathinfo($arq, PATHINFO_EXTENSION) }})
                    </a>
                @endforeach
            </div>

            <div class="flex-1 overflow-auto p-4 flex justify-center items-center">
                @include('ANT::correcao.renderers.' . $dadosVisualizacao['tipo'], ['data' => $dadosVisualizacao])
            </div>
        </div>

        <div class="w-96 bg-white border-l border-gray-200 shadow-xl overflow-y-auto p-6 z-10">
            <h3 class="font-bold text-lg mb-1">{{ $entrega->trabalho->nome }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ $entrega->trabalho->materia->nome }}</p>

            <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                <h4 class="text-xs font-bold text-blue-800 uppercase mb-1">Comentário do Aluno</h4>
                <p class="text-sm text-gray-700 italic">"{{ $entrega->comentario_aluno ?? 'Sem comentários.' }}"</p>
            </div>

            <hr class="my-6">

            <form action="{{ route('ant.correcao.update', $entrega->id) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nota (0 a 10)</label>
                    <input type="number" step="0.1" min="0" max="10" name="nota" value="{{ $entrega->nota }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-bold">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback do Professor</label>
                    <textarea name="comentario_professor" rows="6"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $entrega->comentario_professor }}</textarea>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded hover:bg-indigo-700 transition">
                    Salvar Correção
                </button>
            </form>

            @if(session('success'))
                <div class="mt-4 p-3 bg-green-100 text-green-700 rounded text-sm text-center">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
