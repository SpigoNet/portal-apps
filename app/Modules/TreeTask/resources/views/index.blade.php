@php
    use App\Modules\TreeTask\Support\Aparencia;

    $totalProjetos = $projetos->count();
    $cartoesStats = [
        ['rotulo' => 'Em andamento', 'valor' => $stats['andamento'], 'sub' => 'tarefas ativas', 'cor' => 'var(--tt-ink)'],
        ['rotulo' => 'Atrasadas', 'valor' => $stats['atrasadas'], 'sub' => 'precisam de atenção', 'cor' => $stats['atrasadas'] > 0 ? '#A4231A' : 'var(--tt-ink)'],
        ['rotulo' => 'Vencem esta semana', 'valor' => $stats['semana'], 'sub' => 'até domingo', 'cor' => 'var(--tt-ink)'],
        ['rotulo' => 'Concluídas em 7 dias', 'valor' => $stats['concluidas'], 'sub' => 'bom ritmo', 'cor' => '#14692F'],
    ];
@endphp
<x-TreeTask::layout titulo="Projetos" ativo="projetos" :fab="route('treetask.create')">
    <div x-data="{ filtro: 'todos', busca: '' }" style="display:contents">
        <header class="tt-ph">
            <div>
                <div class="tt-eyebrow">{{ now()->locale('pt_BR')->translatedFormat('l, j \d\e F') }}</div>
                <h1 class="tt-h1">Projetos</h1>
            </div>
            <div class="tt-acoes">
                <label class="tt-busca" style="width:280px;max-width:100%">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input class="tt-input busca" type="search" x-model="busca" placeholder="Buscar projeto" aria-label="Buscar projeto">
                </label>
                <a class="tt-btn pri" href="{{ route('treetask.create') }}">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Novo projeto
                </a>
            </div>
        </header>

        <section class="tt-stats" aria-label="Resumo">
            @foreach($cartoesStats as $c)
                <div class="tt-card tt-stat">
                    <div class="tt-stat-rot">{{ $c['rotulo'] }}</div>
                    <div class="tt-stat-val" style="color: {{ $c['cor'] }}">{{ $c['valor'] }}</div>
                    <div class="tt-stat-sub">{{ $c['sub'] }}</div>
                </div>
            @endforeach
        </section>

        <div class="tt-barra-filtros">
            <div class="tt-seg" role="group" aria-label="Filtro">
                <button type="button" :class="{ on: filtro === 'todos' }" @click="filtro = 'todos'">Todos</button>
                <button type="button" :class="{ on: filtro === 'ativo' }" @click="filtro = 'ativo'">Ativos</button>
                <button type="button" :class="{ on: filtro === 'parado' }" @click="filtro = 'parado'">Parados</button>
                <button type="button" :class="{ on: filtro === 'concluido' }" @click="filtro = 'concluido'">Concluídos</button>
            </div>
            <span class="tt-muted">{{ $totalProjetos }} {{ $totalProjetos === 1 ? 'projeto' : 'projetos' }}</span>
        </div>

        @if($projetos->isEmpty())
            <div class="tt-vazio">
                <p style="margin:0 0 12px;font-weight:600">Nenhum projeto ainda.</p>
                <a class="tt-btn pri" href="{{ route('treetask.create') }}">Criar o primeiro projeto</a>
            </div>
        @else
            <section class="tt-projetos" aria-label="Lista de projetos">
                @foreach($projetos as $projeto)
                    @php
                        $r = $resumos[$projeto->id_projeto];
                        $cor = Aparencia::corProjeto($projeto->id_projeto);
                    @endphp
                    <article class="tt-card tt-pc"
                             x-show="(filtro === 'todos' || filtro === '{{ $r['situacao'] }}') && ({{ \Illuminate\Support\Js::from(mb_strtolower($projeto->nome)) }}.includes(busca.trim().toLowerCase()))"
                             style="position:relative">
                        <a href="{{ route('treetask.lista', $projeto->id_projeto) }}" style="position:absolute;inset:0;border-radius:20px" aria-label="Abrir {{ $projeto->nome }}"></a>
                        <div style="display:flex;gap:14px;align-items:flex-start">
                            <div class="tt-tile" style="background: {{ $cor }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($projeto->nome, 0, 1)) }}</div>
                            <div style="min-width:0;flex:1">
                                <div style="font-weight:700;font-size:17px;letter-spacing:-.01em">{{ $projeto->nome }}</div>
                                <div class="tt-muted tt-clamp2">{{ $projeto->descricao }}</div>
                            </div>
                        </div>
                        <div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px">
                                <span class="tt-muted">Fase atual · <b style="color:var(--tt-ink);font-weight:600">{{ $r['faseAtual'] ?? '—' }}</b></span>
                                <span class="tt-mono" style="font-weight:500">{{ $r['pct'] }}%</span>
                            </div>
                            <div class="tt-bar"><i style="width: {{ $r['pct'] }}%; background: {{ $cor }}"></i></div>
                            <div class="tt-muted" style="font-size:13px;margin-top:8px">{{ $r['feitas'] }} de {{ $r['total'] }} tarefas concluídas</div>
                        </div>
                        <div class="tt-pc-rodape">
                            <span class="tt-avs">
                                @foreach($r['equipe'] as $u)
                                    <span class="tt-av" style="background: {{ Aparencia::corAvatar($u->id) }}" title="{{ $u->name }}">{{ Aparencia::iniciais($u->name) }}</span>
                                @endforeach
                            </span>
                            <span style="display:flex;align-items:center;gap:6px;position:relative;z-index:1">
                                <a class="tt-icbtn" style="width:36px;height:36px" href="{{ route('treetask.kanban', ['projeto' => [$projeto->id_projeto]]) }}" title="Kanban do projeto" aria-label="Kanban de {{ $projeto->nome }}">
                                    <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="10" rx="1.5"/><rect x="17" y="4" width="4" height="13" rx="1.5"/></svg>
                                </a>
                                <a class="tt-icbtn" style="width:36px;height:36px" href="{{ route('treetask.ai.index', ['type' => 'project', 'id' => $projeto->id_projeto]) }}" title="Assistente IA neste projeto" aria-label="Assistente IA em {{ $projeto->nome }}">
                                    <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>
                                </a>
                                <span class="tt-chip {{ $r['classe'] }}">{{ $r['rotulo'] }}</span>
                            </span>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-TreeTask::layout>
