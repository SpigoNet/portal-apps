<x-ANT::layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Painel do Professor') }} <span class="text-sm font-normal text-gray-500">|
                        {{ $semestreAtual }}</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">Olá, Prof. {{ explode(' ', $user->name)[0] }}!</p>
            </div>
        </div>
    </x-slot>

    {{-- Quick Actions Section --}}
    <div class="py-6 bg-gray-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="text-lg font-bold text-gray-700 mb-4 px-2">Ações Rápidas</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('ant.professor.create') }}"
                    class="flex items-center p-3 bg-white rounded-lg shadow-sm hover:shadow border border-indigo-100 group transition-all">
                    <div class="p-2 bg-indigo-50 rounded-full group-hover:bg-indigo-100 text-indigo-600 mr-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-gray-700 group-hover:text-indigo-700">Novo
                            Trabalho</span>
                        <span class="block text-xs text-gray-500">Criar avaliação</span>
                    </div>
                </a>

                <a href="{{ route('ant.pesos.create') }}"
                    class="flex items-center p-3 bg-white rounded-lg shadow-sm hover:shadow border border-indigo-100 group transition-all">
                    <div class="p-2 bg-purple-50 rounded-full group-hover:bg-purple-100 text-purple-600 mr-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-gray-700 group-hover:text-purple-700">Configurar
                            Pesos</span>
                        <span class="block text-xs text-gray-500">Divisão de notas</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($materiasProfessor as $materia)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-bold text-xl text-gray-800">{{ $materia->nome }}</h4>
                                    <span
                                        class="bg-gray-100 text-gray-600 text-xs font-mono px-2 py-1 rounded">{{ $materia->nome_curto }}</span>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="block text-3xl font-bold text-gray-700">{{ $materia->alunos()->count() }}</span>
                                    <span class="text-xs text-gray-400 uppercase">Alunos</span>
                                </div>
                            </div>

                            <hr class="my-3 border-gray-100">

                            <h5 class="font-bold text-sm text-gray-500 mb-2 uppercase">Trabalhos Ativos</h5>

                            @if($materia->trabalhos->isEmpty())
                                <p class="text-sm text-gray-400 italic">Nenhum trabalho criado.</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach($materia->trabalhos as $trabalho)
                                        <li class="flex justify-between items-center text-sm p-2 hover:bg-gray-50 rounded">
                                            <span class="truncate w-1/2" title="{{ $trabalho->nome }}">{{ $trabalho->nome }}</span>

                                            @if($trabalho->pendentes_count > 0)
                                                <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-xs font-bold">
                                                    {{ $trabalho->pendentes_count }} p/ corrigir
                                                </span>
                                            @else
                                                <span class="text-green-600 text-xs flex items-center">
                                                    <span class="material-icons text-xs mr-1">check</span> Em dia
                                                </span>
                                            @endif

                                            <a href="{{ route('ant.professor.trabalho', $trabalho->id) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-bold ml-2">Gerenciar</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end space-x-2">
                                <a href="{{ route('ant.admin.alunos.index', ['materia_id' => $materia->id, 'semestre' => $semestreAtual]) }}"
                                    class="text-gray-600 hover:text-gray-900 text-sm font-medium">Ver Alunos</a>
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('ant.professor.boletim', $materia->id) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-bold">Ver Boletim</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($materiasProfessor->isEmpty())
                <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                    <p class="text-yellow-700">Você não está vinculado a nenhuma matéria no semestre
                        <strong>{{ $semestreAtual }}</strong>.
                    </p>
                </div>
            @endif

        </div>
    </div>
</x-ANT::layout>