<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Meu Foco') }} 🧘
            </h2>
            <a href="{{ route('treetask.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded text-sm">
                Voltar aos Projetos
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-100 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <div>
                <h3 class="text-xl font-black text-blue-800 mb-4 flex items-center uppercase tracking-wide">
                    <span class="bg-blue-100 text-blue-800 p-2 rounded-full mr-3">🔥</span>
                    Fazendo Agora
                </h3>

                @if($emAndamento->count() > 0)
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($emAndamento as $tarefa)
                            <div class="bg-white rounded-xl shadow-lg border-l-8 border-blue-500 overflow-hidden transform hover:scale-[1.01] transition duration-200">
                                <div class="p-6">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="inline-block py-1 px-2 rounded bg-blue-50 text-blue-600 text-xs font-bold uppercase tracking-wider mb-2">
                                                {{ $tarefa->fase->projeto->nome }}
                                            </span>
                                            <h4 class="text-2xl font-bold text-gray-800 mb-2">{{ $tarefa->titulo }}</h4>
                                        </div>

                                        <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="Concluído">
                                            <button type="submit" class="flex items-center bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow hover:shadow-md transition">
                                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Concluir
                                            </button>
                                        </form>
                                    </div>

                                    <p class="text-gray-600 text-lg leading-relaxed mb-4 bg-gray-50 p-4 rounded border border-gray-100">
                                        {{ $tarefa->descricao ?: 'Sem descrição detalhada.' }}
                                    </p>

                                    <div class="flex items-center text-sm text-gray-500 space-x-6">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Vence: {{ $tarefa->data_vencimento ? \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') : '--' }}
                                        </span>
                                        <a href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" class="text-blue-600 hover:underline">Editar Detalhes</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center text-blue-700">
                        Nada em andamento agora. Pegue uma tarefa da lista abaixo! 👇
                    </div>
                @endif
            </div>

            <hr class="border-gray-300">

            <div>
                <h3 class="text-lg font-bold text-gray-700 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Próximas / A Fazer
                </h3>
                <div class="bg-white shadow rounded-lg divide-y divide-gray-100">
                    @forelse($aFazer as $tarefa)
                        <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between group">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST" class="mr-3">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="Em Andamento">
                                        <button type="submit" class="text-gray-300 hover:text-blue-600 transition" title="Iniciar Tarefa">
                                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                                        </button>
                                    </form>

                                    <div>
                                        <span class="text-gray-900 font-medium block">{{ $tarefa->titulo }}</span>
                                        <span class="text-xs text-gray-500">{{ $tarefa->fase->projeto->nome }} > {{ $tarefa->fase->nome }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                @if($tarefa->prioridade == 'Urgente')
                                    <span class="px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded">URGENTE</span>
                                @endif
                                <span class="text-sm text-gray-500">{{ $tarefa->data_vencimento ? \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') : '' }}</span>
                                <a href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-gray-400 text-sm text-center italic">Sem tarefas pendentes.</div>
                    @endforelse
                </div>
            </div>

            @if($concluidas->count() > 0)
                <div class="opacity-75 hover:opacity-100 transition duration-300">
                    <h3 class="text-lg font-bold text-green-700 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Concluídas Recentemente
                    </h3>
                    <div class="bg-white shadow rounded-lg divide-y divide-gray-100 border border-green-200">
                        @foreach($concluidas as $tarefa)
                            <div class="p-4 bg-green-50">
                                <div class="flex justify-between mb-2">
                                    <span class="font-bold text-gray-800 line-through decoration-gray-400 decoration-2">{{ $tarefa->titulo }}</span>

                                    <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="A Fazer">
                                        <button type="submit" class="text-xs text-gray-500 hover:text-gray-800 underline">Reabrir</button>
                                    </form>
                                </div>
                                <div class="text-sm text-gray-600 italic pl-4 border-l-2 border-green-300">
                                    {!! nl2br(e($tarefa->descricao)) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
