<x-TreeTask::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-400 leading-tight">
                {{ __('Modo Zen') }} 🧘
            </h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 flex flex-col items-center py-8">

        @if(isset($focoTotal) && $focoTotal)
            <div class="w-full max-w-4xl px-4 animate-fade-in-up">

                <div class="mb-8 w-full bg-gray-300 rounded-full h-4 overflow-hidden shadow-inner relative" title="Tempo Estimado Visual">
                    <div id="visual-timer-bar" class="bg-gradient-to-r from-blue-500 to-purple-600 h-4 rounded-full transition-all duration-1000 ease-linear" style="width: 0%;"></div>
                </div>

                <div class="bg-white rounded-2xl shadow-2xl border-2 border-indigo-100 overflow-hidden relative">
                    <div class="bg-indigo-600 text-white text-center py-2 text-xs font-bold uppercase tracking-widest">
                        Em Execução Agora
                    </div>

                    <div class="p-8 md:p-12 text-center">
                        <span class="inline-block py-1 px-3 rounded-full bg-indigo-50 text-indigo-600 text-sm font-bold mb-4 border border-indigo-100">
                            {{ $focoTotal->fase->projeto->nome }} &gt; {{ $focoTotal->fase->nome }}
                        </span>

                        <h1 class="text-4xl md:text-5xl font-black text-gray-800 mb-6 leading-tight">
                            {{ $focoTotal->titulo }}
                        </h1>

                        <div class="mb-8 text-left">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Descrição</span>
                                <button type="button" data-btn-editar-desc
                                    onclick="document.getElementById('editor-descricao').classList.remove('hidden'); this.classList.add('hidden');"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 underline font-medium">
                                    ✏️ Editar descrição
                                </button>
                            </div>

                            <div id="visual-descricao" class="prose max-w-none text-gray-600 text-lg bg-gray-50 p-6 rounded-lg border-l-4 border-indigo-400">
                                @if(function_exists('clean'))
                                    {!! clean($focoTotal->descricao) !!}
                                @else
                                    {!! nl2br(e($focoTotal->descricao)) !!}
                                @endif
                            </div>

                            <div id="editor-descricao" class="hidden bg-gray-50 p-6 rounded-lg border-l-4 border-indigo-400">
                                <form action="{{ route('treetask.tarefas.updateDescricao', $focoTotal->id_tarefa) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="descricao" rows="14" placeholder="Elabore a descrição da tarefa..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg text-gray-700 leading-relaxed">{{ old('descricao', $focoTotal->descricao) }}</textarea>
                                    <div class="flex justify-end gap-3 mt-4">
                                        <button type="button"
                                            onclick="document.getElementById('editor-descricao').classList.add('hidden'); document.querySelector('[data-btn-editar-desc]').classList.remove('hidden');"
                                            class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-200 font-medium">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                                            Salvar descrição
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="ai-strategy-area" class="mb-8 hidden bg-gray-900 text-green-400 p-4 rounded-lg text-left font-mono text-sm shadow-inner">
                            <p id="ai-content" class="whitespace-pre-wrap"></p>
                        </div>

                        <div class="flex flex-col md:flex-row justify-center gap-4">
                            <button onclick="solicitarEstrategia({{ $focoTotal->id_tarefa }})" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-bold py-4 px-8 rounded-xl transition transform hover:scale-105 flex items-center justify-center border border-yellow-300">
                                <span class="text-2xl mr-2">🧱</span> Estou Travado / Quebrar Tarefa
                            </button>

                            <form action="{{ route('treetask.tarefas.updateStatus', $focoTotal->id_tarefa) }}" method="POST" class="flex-1 max-w-md">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="Concluído">
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-black text-xl py-4 px-8 rounded-xl shadow-lg transition transform hover:scale-105 flex items-center justify-center">
                                    <span class="mr-2">✅</span> CONCLUIR MISSÃO
                                </button>
                            </form>
                        </div>

                        <div class="mt-6">
                            <form action="{{ route('treetask.tarefas.updateStatus', $focoTotal->id_tarefa) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="A Fazer"> <button type="submit" class="text-gray-400 hover:text-red-500 text-sm font-medium underline">
                                    Pausar / Deixar para depois
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Configuração do Timer Visual
                    // Como temos cegueira temporal, não usamos contagem regressiva numérica, mas visual.
                    // Assumimos que o "bloco" de trabalho é a estimativa ou 1h padrão.
                    const estimativaHoras = {{ $focoTotal->estimativa_tempo ?: 1 }};
                    const totalSegundos = estimativaHoras * 3600;
                    const bar = document.getElementById('visual-timer-bar');

                    // Simulação visual: começa cheio e vai esvaziando (ou enchendo, dependendo da preferência)
                    // Aqui faremos "encher" para dar sensação de progresso/conquista visual
                    let decorrido = 0;

                    // Recuperar estado se houver (opcional, por enquanto resetamos)

                    const interval = setInterval(() => {
                        decorrido++;
                        let pct = (decorrido / totalSegundos) * 100;
                        if(pct > 100) pct = 100;

                        bar.style.width = pct + '%';

                        // Mudança de cor baseada no tempo (Azul -> Roxo -> Vermelho se estourar)
                        if(pct > 80) {
                            bar.classList.remove('from-blue-500', 'to-purple-600');
                            bar.classList.add('bg-red-500');
                        }
                    }, 1000); // Atualiza a cada segundo
                });

                function solicitarEstrategia(taskId) {
                    const area = document.getElementById('ai-strategy-area');
                    const content = document.getElementById('ai-content');

                    area.classList.remove('hidden');
                    content.innerHTML = '🤖 Analisando estrutura da tarefa e calculando micro-passos...';

                    fetch('{{ route("treetask.gamification.motivacao") }}?task_id=' + taskId)
                        .then(res => res.json())
                        .then(data => {
                            content.innerHTML = data.message;
                        })
                        .catch(err => {
                            content.innerHTML = 'Erro ao contactar o estrategista.';
                        });
                }
            </script>

        @else
            <div class="max-w-3xl w-full px-4 space-y-8">

                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-700">O que vamos construir agora?</h3>
                    <p class="text-gray-500">Escolha um bloco para iniciar.</p>
                </div>

                <form method="GET" action="{{ route('treetask.focus.index') }}" class="mb-6 bg-white rounded-xl shadow p-4 flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Projeto</label>
                        <select name="projeto" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos os projetos</option>
                            @foreach($projetos as $p)
                                <option value="{{ $p->id_projeto }}" {{ request('projeto') == $p->id_projeto ? 'selected' : '' }}>{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Prioridade</label>
                        <select name="prioridade" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas</option>
                            @foreach($prioridades as $pr)
                                <option value="{{ $pr }}" {{ request('prioridade') == $pr ? 'selected' : '' }}>{{ $pr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                        <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="todos" {{ $statusFiltro == 'todos' ? 'selected' : '' }}>A Fazer / Planejamento</option>
                            <option value="A Fazer" {{ $statusFiltro == 'A Fazer' ? 'selected' : '' }}>A Fazer</option>
                            <option value="Planejamento" {{ $statusFiltro == 'Planejamento' ? 'selected' : '' }}>Planejamento</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ordenar por</label>
                        <select name="ordenar" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="prioridade" {{ $ordenar == 'prioridade' ? 'selected' : '' }}>Prioridade</option>
                            <option value="vencimento" {{ $ordenar == 'vencimento' ? 'selected' : '' }}>Vencimento</option>
                            <option value="ordem" {{ $ordenar == 'ordem' ? 'selected' : '' }}>Ordem definida</option>
                        </select>
                    </div>

                    @if($filtrosAtivos)
                        <a href="{{ route('treetask.focus.index') }}" class="ml-auto text-sm text-gray-500 hover:text-red-500 underline self-center">
                            Limpar filtros
                        </a>
                    @endif
                </form>

                <div class="space-y-4">
                    @forelse($aFazer as $tarefa)
                        <div class="bg-white p-6 rounded-xl shadow-md border-l-8 border-blue-400 hover:shadow-xl hover:scale-[1.02] transition cursor-pointer group relative overflow-hidden">
                            <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST" class="absolute inset-0 z-10 opacity-0">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="Em Andamento">
                                <button type="submit" class="w-full h-full cursor-pointer"></button>
                            </form>

                            <div class="flex justify-between items-center pointer-events-none">
                                <div>
                                    <span class="text-xs font-bold text-blue-500 uppercase">{{ $tarefa->fase->projeto->nome }}</span>
                                    <h4 class="text-xl font-bold text-gray-800">{{ $tarefa->titulo }}</h4>

                                    @if($tarefa->prioridade == 'Urgente')
                                        <span class="inline-block mt-2 bg-red-100 text-red-700 text-xs px-2 py-1 rounded font-bold">🚨 PRIORIDADE MÁXIMA</span>
                                    @endif
                                </div>
                                <div class="bg-blue-100 text-blue-600 rounded-full p-3 group-hover:bg-blue-600 group-hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                            Sem blocos disponíveis. Vá para Projetos e planeje novos módulos.
                        </div>
                    @endforelse
                </div>

                @if($aguardando->count() > 0)
                    <div class="mt-8 opacity-75">
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-2">Bloqueados / Aguardando</h4>
                        @foreach($aguardando as $tarefa)
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-2 flex justify-between items-center">
                                <span class="text-yellow-800">{{ $tarefa->titulo }}</span>
                                <a href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" class="text-xs text-yellow-600 underline">Ver</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translate3d(0, 20px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
    </style>
</x-TreeTask::layout>
