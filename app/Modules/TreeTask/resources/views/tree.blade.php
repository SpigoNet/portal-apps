<x-TreeTask::layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-2 md:space-y-0">
            <div class="flex items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mr-4">
                    {{ $projeto->nome }}
                </h2>
                <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-600">{{ $projeto->status }}</span>
            </div>

            <div class="flex items-center space-x-2 overflow-x-auto">
                <a href="{{ route('treetask.index') }}" class="text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-1 rounded text-sm shadow-sm whitespace-nowrap">
                    📂 Projetos
                </a>

                @if(Route::currentRouteName() == 'treetask.show')
                    <a href="{{ route('treetask.tree.view', $projeto->id_projeto) }}" class="text-purple-700 hover:text-purple-900 bg-purple-50 border border-purple-200 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                        Ver Árvore 🌳
                    </a>
                @else
                    <a href="{{ route('treetask.show', $projeto->id_projeto) }}" class="text-blue-700 hover:text-blue-900 bg-blue-50 border border-blue-200 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                        Ver Kanban 📋
                    </a>
                @endif

                <span class="text-gray-300">|</span>

                <a href="{{ route('treetask.focus.index') }}" class="text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded text-sm font-bold shadow-sm whitespace-nowrap">
                    Modo Zen 🧘
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        .tree-lateral {
            padding: 50px 20px;
            display: flex; /* Começa com layout horizontal */
            align-items: flex-start;
            min-height: 80vh; /* Altura mínima para rolagem lateral */
            overflow-x: auto;
            background-color: #f8fafc;
        }

        /* Conectores */
        .tree-lateral ul, .tree-lateral li {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
        }

        .tree-lateral ul {
            display: flex;
            flex-direction: column; /* Filhos na vertical */
            padding-left: 50px; /* Espaço para a linha vertical */
        }

        .tree-lateral li {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }

        /* Linhas Verticais e Horizontais */
        .tree-lateral li::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -50px; /* Horizontal para o pai */
            width: 45px;
            height: 0;
            border-top: 2px solid #ccc;
        }
        .tree-lateral li::after {
            content: '';
            position: absolute;
            top: 0;
            left: -50px;
            width: 0;
            height: 100%;
            border-left: 2px solid #ccc; /* Linha vertical que conecta os irmãos */
        }

        /* Ajustes de Pontas e Raiz */
        .tree-lateral li:first-child::after {
            top: 50%; /* Começa no meio do primeiro item */
        }
        .tree-lateral li:last-child::after {
            height: 50%; /* Termina no meio do último item */
            border-radius: 0 0 0 5px;
        }
        .tree-lateral li:only-child::after {
            display: none; /* Se for filho único, não precisa de linha vertical */
        }
        .tree-lateral > li::before {
            border: none !important; /* Raiz não tem linha esquerda */
        }

        /* Ajuste para o UL (vertical line) */
        .tree-lateral ul:not(:first-child)::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -50px;
            width: 50px;
            height: 0;
            border-top: 2px solid #ccc;
        }

        /* Estilo dos Cards (Nós) - Reutilizando o formato arredondado */
        .node {
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 150px;
            transition: all 0.2s;
            position: relative;
            z-index: 10;
        }
        .node:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 10px rgba(0,0,0,0.15);
        }
        .node-root { background-color: #1e293b; color: white; border: 3px solid #0f172a; font-weight: bold; min-width: 200px; }
        .node-phase { background-color: #f1f5f9; border-left: 5px solid #64748b; font-weight: 600; }
        .node-task { background-color: white; border-left: 4px solid #94a3af; font-size: 0.9rem; }

        /* Status Cores */
        .status-concluido { border-color: #10b981 !important; background-color: #ecfdf5 !important; opacity: 0.8; }
        .status-andamento { border-color: #3b82f6 !important; background-color: #eff6ff !important; }
        .status-aguardando { border-color: #f59e0b !important; background-color: #fffbeb !important; }
    </style>

    <div class="tree-lateral">
        <ul>
            <li>
                <div class="node node-root">
                    {{ $projeto->nome }}
                    <div class="text-xs font-normal mt-1 opacity-80">Objetivo Principal</div>
                </div>

                @if($projeto->fases->count() > 0)
                    <ul>
                        @foreach($projeto->fases as $fase)
                            <li>
                                <div class="node node-phase">
                                    {{ $fase->nome }}
                                    <div class="text-xs text-gray-500 mt-1 font-normal">Etapa</div>
                                </div>

                                @if($fase->tarefas->count() > 0)
                                    <ul>
                                        @foreach($fase->tarefas as $tarefa)
                                            <li>
                                                <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa]) }}"
                                                   class="node node-task
                                                   {{ $tarefa->status == 'Concluído' ? 'status-concluido' :
                                                     ($tarefa->status == 'Em Andamento' ? 'status-andamento' :
                                                      ($tarefa->status == 'Aguardando resposta' ? 'status-aguardando' : 'status-afazer')) }}">

                                                    <div class="font-bold text-gray-800">{{ Str::limit($tarefa->titulo, 20) }}</div>

                                                    <div class="text-xs text-gray-500 mt-1">
                                                        Responsável: {{ $tarefa->responsavel->name ? Str::words($tarefa->responsavel->name, 1, '') : 'N/A' }}
                                                    </div>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        </ul>
    </div>
</x-TreeTask::layout>
