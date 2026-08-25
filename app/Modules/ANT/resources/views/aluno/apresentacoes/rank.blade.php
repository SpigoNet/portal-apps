<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.apresentacoes.aluno.rank.index') }}" class="text-gray-500 hover:text-gray-900">Rank de Estrelas</a>
            <span class="text-gray-400 mx-2">/</span>
            {{ $materia->nome }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <div class="font-bold text-gray-900">{{ $materia->nome }}</div>
                    <div class="text-sm text-gray-500">{{ $semestre }}</div>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200">
                            <th class="py-2">#</th>
                            <th class="py-2">Aluno</th>
                            <th class="py-2 text-center">Estrelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rank as $item)
                            <tr class="border-b border-gray-50 {{ $item->aluno->user_id === auth()->id() ? 'bg-indigo-50' : '' }}">
                                <td class="py-2 text-gray-400">{{ $item->posicao }}</td>
                                <td class="py-2 font-medium text-gray-900">
                                    {{ $item->aluno->nome }}
                                    @if($item->aluno->user_id === auth()->id())
                                        <span class="text-xs text-indigo-600 font-semibold ml-1">(você)</span>
                                    @endif
                                    <span class="text-xs text-gray-400 font-mono ml-1">{{ $item->ra }}</span>
                                </td>
                                <td class="py-2 text-center">
                                    <span class="text-yellow-500 font-bold text-lg">{{ $item->estrelas }} ⭐</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="text-xs text-gray-400 mt-4">
                    As estrelas acumulam durante o semestre. No boletim, a nota de participação é
                    normalizada pelo maior número de estrelas da turma.
                </p>
            </div>
        </div>
    </div>
</x-ANT::layout>
