<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projeto: {{ $projeto->nome }}
            </h2>
            <span class="text-sm bg-gray-200 px-3 py-1 rounded-full">{{ $projeto->status }}</span>
        </div>
    </x-slot>

    <div class="py-6 h-screen overflow-x-auto">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 h-full">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex space-x-4 h-full items-start pb-4">

                @foreach($projeto->fases as $fase)
                    <div class="min-w-[300px] bg-gray-100 rounded-lg shadow-md max-h-full flex flex-col">
                        <div class="p-4 border-b border-gray-200 bg-gray-200 rounded-t-lg flex justify-between items-center">
                            <h3 class="font-bold text-gray-700">{{ $fase->nome }}</h3>
                            <span class="text-xs text-gray-500 font-mono">{{ $fase->tarefas->count() }}</span>
                        </div>

                        <div class="p-2 overflow-y-auto flex-1 space-y-2 custom-scrollbar">
                            @foreach($fase->tarefas as $tarefa)
                                <div class="bg-white p-3 rounded border shadow-sm hover:shadow-md transition border-l-4 ...">

                                    <div class="flex justify-between items-start">
                                        <a href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}" class="block flex-1">
                                            <h4 class="font-semibold text-sm text-gray-800 mb-1 hover:text-blue-600">{{ $tarefa->titulo }}</h4>
                                        </a>

                                        <a href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" class="text-gray-400 hover:text-blue-500 ml-2" title="Editar / Mover">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>
                                    </div>

                                    <a href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}" class="block mt-2">
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-2 border-t border-gray-200">
                            <a href="{{ route('treetask.tarefas.create', $fase->id_fase) }}" class="block w-full text-center py-2 px-4 border border-dashed border-gray-400 rounded text-gray-600 hover:bg-gray-200 hover:border-gray-500 text-sm transition">
                                + Adicionar Tarefa
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="min-w-[300px]">
                    <form action="{{ route('treetask.fases.store') }}" method="POST" class="bg-white bg-opacity-50 p-4 rounded-lg border-2 border-dashed border-gray-300">
                        @csrf
                        <input type="hidden" name="id_projeto" value="{{ $projeto->id_projeto }}">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nova Fase</label>
                        <input type="text" name="nome" placeholder="Ex: Validação, Concluído..." class="w-full rounded border-gray-300 shadow-sm text-sm mb-2" required>
                        <button type="submit" class="w-full bg-gray-600 hover:bg-gray-800 text-white py-1 px-2 rounded text-sm">
                            Adicionar Coluna
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
