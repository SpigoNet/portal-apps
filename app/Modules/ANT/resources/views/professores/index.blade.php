<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel do Professor') }} - <span class="text-indigo-600">{{ $semestreAtual }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">Olá, Prof. {{ explode(' ', $user->name)[0] }}!</h3>
                        <p class="text-gray-500">Gestão de Turmas e Notas</p>
                    </div>
                    <div class="flex space-x-2">
                        @if($isAdmin)
                            <a href="{{ route('ant.admin.home') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition flex items-center">
                                <span class="material-icons text-sm mr-2">admin_panel_settings</span>
                                Admin
                            </a>
                        @endif

                        <a href="{{ route('ant.pesos.create') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition flex items-center">
                            <span class="material-icons text-sm mr-2">pie_chart</span>
                            Configurar Pesos
                        </a>
                        <a href="{{ route('ant.professor.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition inline-block">
                            + Novo Trabalho / Prova
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($materiasProfessor as $materia)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-bold text-xl text-gray-800">{{ $materia->nome }}</h4>
                                    <span class="bg-gray-100 text-gray-600 text-xs font-mono px-2 py-1 rounded">{{ $materia->nome_curto }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-3xl font-bold text-gray-700">{{ $materia->alunos()->count() }}</span>
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

                                            <a href="{{ route('ant.professor.trabalho', $trabalho->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold ml-2">Gerenciar</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end space-x-2">
                                <a href="#" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Ver Alunos</a>
                                <span class="text-gray-300">|</span>
                                <a href="#" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Lançar Notas</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($materiasProfessor->isEmpty())
                <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                    <p class="text-yellow-700">Você não está vinculado a nenhuma matéria no semestre <strong>{{ $semestreAtual }}</strong>.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
