<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.apresentacoes.index') }}" class="text-gray-500 hover:text-gray-900">Apresentações</a>
            <span class="text-gray-400 mx-2">/</span>
            Rank de Estrelas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <x-ANT::rank-podium :rank="$rank" :materia="$materia" :semestre="$semestreAtual" />
            </div>
        </div>
    </div>
</x-ANT::layout>
