<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.home') }}" class="text-gray-500 hover:text-gray-900">Minhas Aulas</a>
            <span class="text-gray-400 mx-2">/</span>
            Rank de Estrelas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">
                        Selecione a disciplina para ver o ranking de participação (estrelas) do semestre.
                    </p>

                    @forelse($apresentacoes as $apresentacao)
                        <div class="border-b border-gray-100 last:border-0 py-3 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-gray-900">{{ $apresentacao->materia->nome }}</div>
                                <div class="text-sm text-gray-500">{{ $apresentacao->nome }}</div>
                            </div>
                            <a href="{{ route('ant.apresentacoes.aluno.rank', $apresentacao->materia_id) }}"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                Ver rank →
                            </a>
                        </div>
                    @empty
                        <p class="text-gray-500 italic text-center py-8">
                            Nenhuma apresentação cadastrada nas suas disciplinas neste semestre.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-ANT::layout>
