@php
    use App\Modules\TreeTask\Support\Aparencia;

    $arvore = '<path d="M12 21v-8M12 13c-4 0-6-2-6-5M12 13c4 0 6-2 6-5M6 8V4M18 8V4M12 13V3"/><circle cx="12" cy="21" r="1.7" fill="#C9E86B" stroke="none"/>';
@endphp

@if(isset($focoTotal) && $focoTotal)
    {{-- Foco total: tela escura, sem menus, uma única tarefa --}}
    <x-TreeTask::layout titulo="Modo foco" :bare="true">
        <style>
            .tt-foco { min-height: 100vh; color: #EDEFF5; background: radial-gradient(900px 500px at 50% -10%, #3A2457 0%, #17121D 70%); padding: 24px 24px 56px; display: flex; flex-direction: column; align-items: center; gap: 28px; font-size: 15px; line-height: 1.5; }
            .tt-foco .tt-btn { min-height: 48px; padding: 0 20px; border-color: #4A3F5C; background: transparent; color: #EDEFF5; font-size: 15px; }
            .tt-foco .tt-btn:hover { background: #2A2236; }
            .tt-foco .tt-btn.pri { background: #8A58BD; border-color: #8A58BD; color: #fff; }
            .tt-foco .tt-btn.pri:hover { background: #9D6FCC; }
            .tt-foco-w { width: 100%; max-width: 960px; }
            .tt-foco-card { background: #211A2B; border: 1px solid #342A43; border-radius: 28px; padding: 56px clamp(24px, 6vw, 72px); display: flex; flex-direction: column; gap: 28px; box-shadow: 0 30px 80px rgba(0, 0, 0, .35); }
            .tt-foco-desc { background: #1B1524; border: 1px solid #342A43; border-radius: 16px; padding: 24px 28px; color: #D8D0E3; font-size: 17px; line-height: 1.7; overflow-wrap: anywhere; }
            .tt-foco-desc p { margin: 0 0 10px; }
            .tt-foco-rot { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #A79DB8; }
            .tt-foco .tt-textarea { background: #17121D; color: #EDEFF5; border-color: #4A3F5C; font-size: 16px; }
            .tt-foco-q { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background: #211A2B; border: 1px solid #342A43; text-decoration: none; color: #EDEFF5; }
            .tt-foco-q:hover { background: #2A2236; }
            .tt-foco-ia { background: #12141C; color: #8BE0A4; border-radius: 12px; padding: 16px; font: 500 13px/1.6 'JetBrains Mono', monospace; white-space: pre-wrap; }
        </style>

        <div class="tt-foco">
            <header class="tt-foco-w" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
                <a href="{{ route('treetask.index') }}" style="display:inline-flex;align-items:center;gap:8px;color:#B9AFC9;text-decoration:none;font-weight:600;min-height:44px">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>Sair do foco
                </a>
                <span class="tt-brand-icone" style="width:36px;height:36px;border-radius:11px"><svg viewBox="0 0 24 24" aria-hidden="true" style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round">{!! $arvore !!}</svg></span>
                <div style="display:flex;align-items:center;gap:12px;color:#B9AFC9;font-weight:500">
                    <span class="tt-mono" id="foco-relogio" style="font-size:15px;color:#EDEFF5">00:00:00</span>
                    <span>nesta sessão</span>
                </div>
            </header>

            <div class="tt-foco-w" style="height:6px;border-radius:99px;background:#342A43;overflow:hidden" role="progressbar" aria-label="Tempo estimado" aria-valuemin="0" aria-valuemax="100" id="foco-progresso">
                <div id="visual-timer-bar" style="width:0%;height:100%;border-radius:99px;background:linear-gradient(90deg,#8A58BD,#C9E86B);transition:width 1s linear"></div>
            </div>

            @if(session('success'))
                <div class="tt-alert ok tt-foco-w" role="status">{{ session('success') }}</div>
            @endif

            <main class="tt-foco-w tt-foco-card" x-data="{ editando: false }">
                <div style="display:flex;flex-direction:column;gap:20px;align-items:flex-start">
                    <span style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:#3A2A55;color:#E0CBF5;font-weight:700;font-size:13px">
                        {{ $focoTotal->fase->projeto->nome }}
                        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        {{ $focoTotal->fase->nome }}
                    </span>
                    <h1 style="font-size:clamp(32px,5vw,54px);line-height:1.08;font-weight:800;letter-spacing:-.03em">{{ $focoTotal->titulo }}</h1>
                </div>

                <section aria-label="Descrição" class="tt-foco-desc">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="tt-foco-rot">Descrição</span>
                        <button type="button" x-show="! editando" @click="editando = true" style="background:none;border:0;color:#D6BCF2;font:600 14px 'Hanken Grotesk',sans-serif;cursor:pointer;min-height:44px;padding:0 4px">Editar descrição</button>
                    </div>

                    <div x-show="! editando">
                        @if(trim(strip_tags((string) $focoTotal->descricao)) !== '')
                            @if(function_exists('clean')) {!! clean($focoTotal->descricao) !!} @else {!! nl2br(e($focoTotal->descricao)) !!} @endif
                        @else
                            <p style="margin:0;color:#A79DB8">Sem descrição.</p>
                        @endif
                    </div>

                    <form x-show="editando" x-cloak action="{{ route('treetask.tarefas.updateDescricao', $focoTotal->id_tarefa) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <textarea class="tt-textarea" name="descricao" rows="12" placeholder="Elabore a descrição da tarefa...">{{ old('descricao', $focoTotal->descricao) }}</textarea>
                        <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:12px;margin-top:14px">
                            <button type="button" class="tt-btn" @click="editando = false">Cancelar</button>
                            <button type="submit" class="tt-btn pri">Salvar descrição</button>
                        </div>
                    </form>
                </section>

                <div id="ai-strategy-area" class="tt-foco-ia" style="display:none"><span id="ai-content"></span></div>

                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
                    <form action="{{ route('treetask.tarefas.updateStatus', $focoTotal->id_tarefa) }}" method="POST" style="flex:1 1 240px;display:flex">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Concluído">
                        <button type="submit" class="tt-btn pri" style="flex:1;min-height:56px;font-size:17px">
                            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>Concluir e comemorar
                        </button>
                    </form>
                    <button type="button" class="tt-btn" onclick="solicitarEstrategia({{ $focoTotal->id_tarefa }})">
                        <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>Estou travado
                    </button>
                    <form action="{{ route('treetask.tarefas.updateStatus', $focoTotal->id_tarefa) }}" method="POST" style="display:flex">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="A Fazer">
                        <button type="submit" class="tt-btn">
                            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg>Pausar
                        </button>
                    </form>
                </div>
            </main>

            @if($aFazer->isNotEmpty())
                <section class="tt-foco-w" aria-label="Em seguida">
                    <div class="tt-foco-rot" style="margin-bottom:10px">Em seguida</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px">
                        @foreach($aFazer->take(3) as $proxima)
                            <a class="tt-foco-q" href="{{ route('treetask.tarefas.show', $proxima->id_tarefa) }}">
                                <span class="tt-prio {{ Aparencia::prioridadeClasse($proxima->prioridade) }}" style="font-size:0"></span>
                                <span style="min-width:0;flex:1">
                                    <span style="display:block;font-weight:600">{{ $proxima->titulo }}</span>
                                    <span style="display:block;font-size:13px;color:#A79DB8">{{ $proxima->fase->projeto->nome }} · {{ $proxima->prioridade }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Progresso visual: o bloco de trabalho é a estimativa da tarefa (ou 1h), sem contagem regressiva numérica.
                const totalSegundos = {{ $focoTotal->estimativa_tempo ?: 1 }} * 3600;
                const barra = document.getElementById('visual-timer-bar');
                const progresso = document.getElementById('foco-progresso');
                const relogio = document.getElementById('foco-relogio');
                let decorrido = 0;

                const dois = n => String(n).padStart(2, '0');
                setInterval(() => {
                    decorrido++;
                    const pct = Math.min(100, (decorrido / totalSegundos) * 100);
                    barra.style.width = pct + '%';
                    progresso.setAttribute('aria-valuenow', Math.round(pct));
                    if (pct > 80) barra.style.background = '#EF4444';
                    relogio.textContent = dois(Math.floor(decorrido / 3600)) + ':' + dois(Math.floor(decorrido / 60) % 60) + ':' + dois(decorrido % 60);
                }, 1000);
            });

            function solicitarEstrategia(taskId) {
                const area = document.getElementById('ai-strategy-area');
                const content = document.getElementById('ai-content');

                area.style.display = 'block';
                content.textContent = 'Analisando a tarefa e calculando micro-passos...';

                fetch('{{ route("treetask.gamification.motivacao") }}?task_id=' + taskId)
                    .then(res => res.json())
                    .then(data => { content.textContent = data.message; })
                    .catch(() => { content.textContent = 'Erro ao contactar o estrategista.'; });
            }
        </script>
    </x-TreeTask::layout>
@else
    {{-- Escolha do próximo bloco --}}
    <x-TreeTask::layout titulo="Modo foco" ativo="foco">
        <header>
            <div class="tt-eyebrow">Um bloco de cada vez</div>
            <h1 class="tt-h1">Modo foco</h1>
        </header>

        <section style="max-width:820px;display:flex;flex-direction:column;gap:18px">
            <div>
                <h2 style="font-size:20px;font-weight:700">O que vamos construir agora?</h2>
                <p class="tt-muted" style="margin:4px 0 0">Escolha um bloco para iniciar.</p>
            </div>

            <form method="GET" action="{{ route('treetask.focus.index') }}" class="tt-filtros">
                <label class="tt-flt">Projeto:
                    <select name="projeto" onchange="this.form.submit()" aria-label="Projeto">
                        <option value="">Todos</option>
                        @foreach($projetos as $p)
                            <option value="{{ $p->id_projeto }}" @selected(request('projeto') == $p->id_projeto)>{{ $p->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="tt-flt">Prioridade:
                    <select name="prioridade" onchange="this.form.submit()" aria-label="Prioridade">
                        <option value="">Todas</option>
                        @foreach($prioridades as $pr)
                            <option value="{{ $pr }}" @selected(request('prioridade') == $pr)>{{ $pr }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="tt-flt">Status:
                    <select name="status" onchange="this.form.submit()" aria-label="Status">
                        <option value="todos" @selected($statusFiltro == 'todos')>A Fazer / Planejamento</option>
                        <option value="A Fazer" @selected($statusFiltro == 'A Fazer')>A Fazer</option>
                        <option value="Planejamento" @selected($statusFiltro == 'Planejamento')>Planejamento</option>
                    </select>
                </label>
                <label class="tt-flt">Ordenar por:
                    <select name="ordenar" onchange="this.form.submit()" aria-label="Ordenar por">
                        <option value="prioridade" @selected($ordenar == 'prioridade')>Prioridade</option>
                        <option value="vencimento" @selected($ordenar == 'vencimento')>Vencimento</option>
                        <option value="ordem" @selected($ordenar == 'ordem')>Ordem definida</option>
                    </select>
                </label>
                @if($filtrosAtivos)
                    <a href="{{ route('treetask.focus.index') }}" class="tt-btn ghost">Limpar filtros</a>
                @endif
            </form>

            <div style="display:flex;flex-direction:column;gap:12px">
                @forelse($aFazer as $tarefa)
                    <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Em Andamento">
                        <button type="submit" class="tt-card tt-pc" style="width:100%;text-align:left;cursor:pointer;flex-direction:row;align-items:center;justify-content:space-between;gap:16px;font:inherit">
                            <span style="min-width:0">
                                <span style="display:block;font-size:12px;font-weight:700;color: {{ Aparencia::corProjeto($tarefa->fase->projeto->id_projeto) }}">{{ $tarefa->fase->projeto->nome }}</span>
                                <span style="display:block;font-size:18px;font-weight:700;letter-spacing:-.01em">{{ $tarefa->titulo }}</span>
                                <span style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;align-items:center">
                                    <span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}">{{ $tarefa->prioridade }}</span>
                                    @if($tarefa->data_vencimento)
                                        <span class="tt-meta">{{ $tarefa->data_vencimento->format('d/m') }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="tt-icbtn" style="background:var(--tt-brand-soft);color:var(--tt-brand-d);width:48px;height:48px;border-radius:50%" aria-hidden="true">
                                <svg class="tt-i l" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m10 8.5 5 3.5-5 3.5z"/></svg>
                            </span>
                        </button>
                    </form>
                @empty
                    <div class="tt-vazio">Sem blocos disponíveis. Vá para Projetos e planeje novos módulos.</div>
                @endforelse
            </div>

            @if($aguardando->count() > 0)
                <div>
                    <h2 class="tt-sec-tit" style="margin-bottom:10px">Bloqueadas · aguardando resposta</h2>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        @foreach($aguardando as $tarefa)
                            <div class="tt-card" style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px">
                                <span style="font-weight:600">{{ $tarefa->titulo }}</span>
                                <a class="tt-btn ghost sm" href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}">Ver</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </x-TreeTask::layout>
@endif
