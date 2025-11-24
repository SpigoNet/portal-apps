<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Meu Foco') }} 🧘
            </h2>

            <div class="flex space-x-2">
                <a href="{{ route('treetask.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-2 px-4 rounded shadow-sm text-sm">
                    📂 Voltar aos Projetos
                </a>
            </div>
        </div>
    </x-slot>
    <style>
        .rich-text ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .rich-text ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        .rich-text li { margin-bottom: 0.25rem; }
        .rich-text p { margin-bottom: 0.75rem; line-height: 1.6; }
        .rich-text strong { font-weight: 700; color: #1f2937; }
        .rich-text em { font-style: italic; }
        .rich-text h1 { font-size: 1.5rem; font-weight: 800; margin-top: 1.5rem; margin-bottom: 1rem; }
        .rich-text h2 { font-size: 1.25rem; font-weight: 700; margin-top: 1.25rem; margin-bottom: 0.75rem; }
        .rich-text blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; font-style: italic; color: #4b5563; margin-bottom: 1rem; }
        .rich-text a { color: #2563eb; text-decoration: underline; }
    </style>
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

                                    <div class="text-gray-600 text-lg leading-relaxed mb-4 bg-gray-50 p-4 rounded border border-gray-100">
                                        @if(function_exists('clean'))
                                            {!! clean($tarefa->descricao)  ?: 'Sem descrição detalhada.' !!}
                                        @else
                                            {!! $tarefa->descricao  ?: 'Sem descrição detalhada.' !!}
                                        @endif
                                    </div>

                                    <div class="flex items-center text-sm text-gray-500 space-x-6">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Vence: {{ $tarefa->data_vencimento ? \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') : '--' }}
                                    </span>
                                        <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa, 'origin' => 'focus']) }}" class="text-blue-600 hover:underline">Editar Detalhes</a>
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

            <hr class="border-gray-300 mt-8 mb-8">

            <div>
                <h3 class="text-xl font-black text-orange-700 mb-4 flex items-center uppercase tracking-wide">
                    <span class="bg-yellow-100 text-yellow-700 p-2 rounded-full mr-3">✋</span>
                    Aguardando Resposta
                </h3>

                @if($aguardando->count() > 0)
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($aguardando as $tarefa)
                            <div class="bg-white rounded-xl shadow-lg border-l-8 border-yellow-500 overflow-hidden">
                                <div class="p-6">
                                    <div class="flex justify-between items-start">
                                        <div>
                            <span class="inline-block py-1 px-2 rounded bg-yellow-50 text-yellow-600 text-xs font-bold uppercase tracking-wider mb-2">
                                {{ $tarefa->fase->projeto->nome }}
                            </span>
                                            <h4 class="text-2xl font-bold text-gray-800 mb-2">{{ $tarefa->titulo }}</h4>
                                        </div>
                                        <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa, 'origin' => 'focus']) }}" class="text-gray-500 hover:text-blue-600 font-bold py-1 px-3 rounded-lg border border-gray-300 shadow-sm transition text-sm">
                                            Detalhes / Reabrir
                                        </a>
                                    </div>
                                    <p class="text-gray-600 text-base leading-relaxed mb-4">
                                        {{ $tarefa->descricao ?: 'Sem descrição detalhada.' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700">
                        Nenhuma tarefa bloqueada no momento.
                    </div>
                @endif
            </div>

            <hr class="border-gray-300 mt-8 mb-8">

            <div>
                <h3 class="text-lg font-bold text-gray-700 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Próximas / A Fazer
                </h3>

                @php
                    // Definição das prioridades e suas cores/ícones para exibição
                    $prioridades = [
                        'Urgente' => ['color' => 'red', 'icon' => '🚨', 'bg' => 'bg-red-50', 'border' => 'border-red-200'],
                        'Alta'    => ['color' => 'orange', 'icon' => '🔥', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200'],
                        'Média'   => ['color' => 'blue', 'icon' => '🔹', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200'],
                        'Baixa'   => ['color' => 'gray', 'icon' => '☕', 'bg' => 'bg-gray-50', 'border' => 'border-gray-200'],
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach($prioridades as $nomePrioridade => $estilo)
                        @php
                            // Filtra as tarefas desta prioridade específica
                            $tarefasDoGrupo = $aFazer->filter(function($t) use ($nomePrioridade) {
                                return $t->prioridade === $nomePrioridade;
                            });
                        @endphp

                        <details class="group bg-white rounded-lg shadow-sm border {{ $estilo['border'] }}" {{ $nomePrioridade === 'Urgente' ? 'open' : '' }}>
                            <summary class="flex justify-between items-center cursor-pointer p-4 {{ $estilo['bg'] }} rounded-t-lg hover:opacity-90 transition select-none">
                                <div class="flex items-center">
                                    <span class="mr-2 text-xl">{{ $estilo['icon'] }}</span>
                                    <span class="font-bold text-gray-800">{{ $nomePrioridade }}</span>
                                    <span class="ml-2 bg-white text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full border border-gray-200">
                                    {{ $tarefasDoGrupo->count() }}
                                </span>
                                </div>
                                <div class="text-gray-400 group-open:rotate-180 transition-transform duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </summary>

                            <div class="p-2 bg-gray-50 border-t {{ $estilo['border'] }}">
                                <div class="focus-list-sortable space-y-2 min-h-[10px]" data-priority-group="{{ $nomePrioridade }}">
                                    @forelse($tarefasDoGrupo as $tarefa)
                                        <div data-tarefa-id="{{ $tarefa->id_tarefa }}" class="bg-white p-4 rounded shadow-sm hover:shadow-md transition flex items-center justify-between group cursor-move border-l-4" style="border-left-color: {{ $estilo['color'] }}">
                                            <div class="mr-2 text-gray-300 cursor-move">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                            </div>
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
                                                @if($tarefa->data_vencimento)
                                                    @php
                                                        $venceHoje = \Carbon\Carbon::parse($tarefa->data_vencimento)->isToday();
                                                        $vencida = \Carbon\Carbon::parse($tarefa->data_vencimento)->isPast() && !$venceHoje;
                                                    @endphp
                                                    <span class="text-sm {{ $vencida ? 'text-red-600 font-bold' : ($venceHoje ? 'text-orange-600 font-bold' : 'text-gray-500') }}">
                                                    {{ \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') }}
                                                </span>
                                                @endif
                                                <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa, 'origin' => 'focus']) }}" class="text-gray-400 hover:text-blue-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </a>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-gray-400 text-sm text-center italic border-dashed border border-gray-300 rounded">
                                            Nenhuma tarefa com prioridade {{ $nomePrioridade }}.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    @endforeach
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
    <script src="[https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js](https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js)"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Seleciona todas as listas (agora temos 4 possíveis)
            const containers = document.querySelectorAll('.focus-list-sortable');

            containers.forEach(container => {
                new Sortable(container, {
                    group: 'prioridades', // Permite mover entre prioridades visualmente (NOTA: Isso não altera a prioridade no banco automaticamente, apenas a ordem global)
                    animation: 150,
                    ghostClass: 'bg-indigo-50',
                    onEnd: function (evt) {
                        // Coleta IDs da lista de destino para salvar a nova ordem
                        let ids = Array.from(evt.to.querySelectorAll('[data-tarefa-id]'))
                            .map(el => el.getAttribute('data-tarefa-id'));

                        fetch('{{ route("treetask.reorder.global") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ ids: ids })
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
