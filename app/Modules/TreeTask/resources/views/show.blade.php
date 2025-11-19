<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-2 md:space-y-0">
            <div class="flex items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mr-4">
                    {{ $projeto->nome }}
                </h2>
                <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-600">{{ $projeto->status }}</span>
            </div>

            <div class="flex items-center space-x-2 overflow-x-auto">
                <a href="{{ route('treetask.index') }}"
                   class="text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-1 rounded text-sm shadow-sm whitespace-nowrap">
                    📂 Projetos
                </a>

                @if(Route::currentRouteName() == 'treetask.show')
                    <a href="{{ route('treetask.tree.view', $projeto->id_projeto) }}"
                       class="text-purple-700 hover:text-purple-900 bg-purple-50 border border-purple-200 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                        Ver Árvore 🌳
                    </a>
                @else
                    <a href="{{ route('treetask.show', $projeto->id_projeto) }}"
                       class="text-blue-700 hover:text-blue-900 bg-blue-50 border border-blue-200 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                        Ver Kanban 📋
                    </a>
                @endif

                <span class="text-gray-300">|</span>

                <a href="{{ route('treetask.focus.index') }}"
                   class="text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                    Modo Zen 🧘
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 h-screen overflow-x-auto">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 h-full">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div id="kanban-container" class="flex space-x-4 h-full items-start pb-4">
                @foreach($projeto->fases as $fase)
                    <div data-fase-id="{{ $fase->id_fase }}"
                         class="min-w-[300px] bg-gray-100 rounded-lg shadow-md max-h-full flex flex-col">
                        <div
                            class="fase-handle cursor-move p-4 border-b border-gray-200 bg-gray-200 rounded-t-lg flex justify-between items-center">
                            <h3 class="font-bold text-gray-700">{{ $fase->nome }}</h3>
                            <span class="text-xs text-gray-500 font-mono">{{ $fase->tarefas->count() }}</span>
                        </div>

                        <div class="tarefas-container p-2 overflow-y-auto flex-1 space-y-2 custom-scrollbar"
                             id="fase-{{ $fase->id_fase }}">
                            @foreach($fase->tarefas as $tarefa)
                                @php
                                    $isConcluido = ($tarefa->status == 'Concluído');
                                    $priorityClass = '';
                                    if (!$isConcluido) {
                                        $priorityClass = match ($tarefa->prioridade) {
                                            'Urgente' => 'border-red-500',
                                            'Alta' => 'border-orange-400',
                                            default => 'border-blue-400',
                                        };
                                    }
                                @endphp

                                <div data-tarefa-id="{{ $tarefa->id_tarefa }}" class="bg-white p-3 rounded border shadow-sm transition cursor-grab
        {{ $isConcluido ? 'opacity-70 bg-green-50 border-green-400' : 'hover:shadow-md' }} border-l-4
        {{ $isConcluido ? 'border-green-400' : $priorityClass }}">

                                    <div class="flex justify-between items-start">

                                        <a href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}" class="block flex-1">
                                            <h4 class="font-semibold text-sm mb-1
                    {{ $isConcluido ? 'line-through text-gray-500' : 'text-gray-800 hover:text-blue-600' }}">
                                                {{ $tarefa->titulo }}
                                            </h4>
                                        </a>

                                        <a href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" class="text-gray-400 hover:text-blue-500 ml-2 flex-shrink-0" title="Editar / Mover">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>
                                    </div>

                                    <a href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}" class="block mt-2">
                                        <div class="flex justify-between items-center text-xs">
                                            @if($tarefa->responsavel)
                                                <div class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full" title="Responsável">
                                                    {{ Str::limit($tarefa->responsavel->name, 10) }}
                                                </div>
                                            @else
                                                <span></span>
                                            @endif

                                            @if($tarefa->data_vencimento)
                                                <span class="{{ $isConcluido ? 'text-green-600' : (\Carbon\Carbon::parse($tarefa->data_vencimento)->isPast() ? 'text-red-600 font-bold' : 'text-gray-500') }}">
                        {{ $isConcluido ? 'CONCLUÍDO' : \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') }}
                    </span>
                                            @else
                                                <span class="{{ $isConcluido ? 'text-green-600' : 'text-gray-400' }}">{{ $isConcluido ? 'CONCLUÍDO' : '' }}</span>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-2 border-t border-gray-200">
                            <a href="{{ route('treetask.tarefas.create', $fase->id_fase) }}"
                               class="block w-full text-center py-2 px-4 border border-dashed border-gray-400 rounded text-gray-600 hover:bg-gray-200 hover:border-gray-500 text-sm transition">
                                + Adicionar Tarefa
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="min-w-[300px]">
                    <form action="{{ route('treetask.fases.store') }}" method="POST"
                          class="bg-white bg-opacity-50 p-4 rounded-lg border-2 border-dashed border-gray-300">
                        @csrf
                        <input type="hidden" name="id_projeto" value="{{ $projeto->id_projeto }}">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nova Fase</label>
                        <input type="text" name="nome" placeholder="Ex: Validação, Concluído..."
                               class="w-full rounded border-gray-300 shadow-sm text-sm mb-2" required>
                        <button type="submit"
                                class="w-full bg-gray-600 hover:bg-gray-800 text-white py-1 px-2 rounded text-sm">
                            Adicionar Coluna
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Funcao genérica para enviar a ordem
            function saveOrder(url, ids) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ids: ids})
                }).then(response => console.log('Ordem salva'));
            }

            // 1. Ordenação de FASES (Horizontal)
            const fasesContainer = document.getElementById('kanban-container');
            new Sortable(fasesContainer, {
                animation: 150,
                handle: '.fase-handle', // Só arrasta se clicar no título/handle
                ghostClass: 'bg-blue-100',
                onEnd: function () {
                    let ids = Array.from(fasesContainer.querySelectorAll('[data-fase-id]'))
                        .map(el => el.getAttribute('data-fase-id'));
                    saveOrder('{{ route("treetask.reorder.fases") }}', ids);
                }
            });

            // 2. Ordenação de TAREFAS (Vertical dentro das fases)
            document.querySelectorAll('.tarefas-container').forEach(container => {
                new Sortable(container, {
                    group: 'tarefas', // Permite mover entre fases (visual apenas, persistencia requer logica extra*)
                    animation: 150,
                    ghostClass: 'bg-gray-200',
                    onEnd: function (evt) {
                        // Se mudou apenas a ordem na mesma coluna
                        let ids = Array.from(evt.to.querySelectorAll('[data-tarefa-id]'))
                            .map(el => el.getAttribute('data-tarefa-id'));

                        saveOrder('{{ route("treetask.reorder.tarefas") }}', ids);

                        // *Nota: Se quiser permitir mover de uma fase para outra via DragDrop,
                        // a lógica seria mais complexa (atualizar id_fase).
                        // Por enquanto, focamos na ORDENAÇÃO dentro da lista.
                        // A movimentação de fase continua via Edição/Select para robustez.
                    }
                });
            });
        });
    </script>
</x-app-layout>
