<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
            <span class="text-gray-400 mx-2">/</span>
            Apresentações
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-700">Calendário de Apresentações</h3>
                <a href="{{ route('ant.professor.apresentacoes.create') }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                    + Nova Apresentação
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($apresentacoes as $apresentacao)
                        @php
                            $totalAgendamentos = $apresentacao->agendamentos->count();
                            $totalEstrelas = $totalAgendamentos * 3;
                        @endphp
                        <div class="border-b border-gray-100 last:border-0 py-4 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-gray-900">{{ $apresentacao->nome }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $apresentacao->materia->nome }}
                                    · {{ $totalAgendamentos }} data(s) · {{ $totalEstrelas }} estrela(s) em jogo
                                </div>
                            </div>
                            <a href="{{ route('ant.professor.apresentacoes.show', $apresentacao->id) }}"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                Gerenciar →
                            </a>
                        </div>
                    @empty
                        <p class="text-gray-500 italic text-center py-8">
                            Nenhuma apresentação criada ainda.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-ANT::layout>
