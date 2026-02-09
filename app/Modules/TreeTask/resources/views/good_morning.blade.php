<x-TreeTask::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight text-gray-400 hover:text-white">
                {{ __('Bom Dia! Vamos revisar seus projetos esquecidos?') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($projetos as $projeto)
                    @php
                        $days = $projeto->days_since_interaction;
                        $colorClass = $days > 30 ? 'bg-red-50 border-red-200' : ($days > 7 ? 'bg-yellow-50 border-yellow-200' : 'bg-white border-gray-200');
                        $textClass = $days > 30 ? 'text-red-700' : ($days > 7 ? 'text-yellow-700' : 'text-gray-700');
                    @endphp
                    <div class="overflow-hidden shadow-sm sm:rounded-lg border {{ $colorClass }} p-6 flex flex-col h-full">

                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-gray-900">{{ $projeto->nome }}</h3>
                            <span
                                class="text-xs font-semibold px-2 py-1 rounded-full {{ $textClass }} bg-opacity-20 border border-current">
                                {{ $days }} dias sem mexer
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mb-4 flex-grow">{{ Str::limit($projeto->descricao, 100) }}</p>

                        @if($projeto->proximas_tarefas->isNotEmpty())
                            <div class="mb-4">
                                <h4 class="text-xs uppercase font-semibold text-gray-500 mb-2">Próximas Tarefas:</h4>
                                <ul class="space-y-2">
                                    @foreach($projeto->proximas_tarefas as $tarefa)
                                        <li class="flex items-center text-sm">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $tarefa->prioridade == 'Alta' ? 'bg-red-500' : 'bg-blue-400' }} mr-2"></span>
                                            <span class="truncate text-gray-700"
                                                title="{{ $tarefa->titulo }}">{{ $tarefa->titulo }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="mb-4 text-sm text-gray-400 italic">
                                Sem tarefas pendentes.
                            </div>
                        @endif

                        <div class="mt-auto pt-4 border-t border-gray-200/50">
                            <a href="{{ route('treetask.show', $projeto->id_projeto) }}"
                                class="block w-full text-center bg-white border border-gray-300 text-gray-700 font-medium py-2 px-4 rounded hover:bg-gray-50 transition duration-150 ease-in-out">
                                Ir para o Projeto
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-10 text-gray-500">
                        <p class="text-xl">Você está em dia com todos os seus projetos! 🎉</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-TreeTask::layout>