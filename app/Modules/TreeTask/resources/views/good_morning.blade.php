@php
    use App\Modules\TreeTask\Support\Aparencia;

    $hora = (int) now()->format('G');
    $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
    $primeiroNome = \Illuminate\Support\Str::words((string) auth()->user()?->name, 1, '');
@endphp
<x-TreeTask::layout titulo="Bom dia" ativo="bomdia">
    <header>
        <div class="tt-eyebrow">{{ now()->locale('pt_BR')->translatedFormat('l, j \d\e F') }}</div>
        <h1 class="tt-h1" style="font-size:38px">{{ $saudacao }}, {{ $primeiroNome }}</h1>
        <p class="tt-muted" style="margin:6px 0 0;font-size:16px;max-width:620px">
            @if($projetos->isNotEmpty())
                Estes projetos estão esperando por você. Escolha um e dê o primeiro passo.
            @else
                Nenhum projeto ativo por aqui.
            @endif
        </p>
    </header>

    <section class="tt-bomdia" aria-label="Projetos esquecidos">
        @forelse($projetos as $projeto)
            @php
                $dias = (int) $projeto->days_since_interaction;
                $classe = $dias > 30 ? 'chip-risco' : ($dias > 7 ? 'chip-aviso' : 'chip-ok');
            @endphp
            <article class="tt-card tt-bd">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                    <h2 style="font-size:18px;font-weight:700;letter-spacing:-.01em">{{ $projeto->nome }}</h2>
                    <span class="tt-chip {{ $classe }}">{{ $dias }} {{ $dias === 1 ? 'dia' : 'dias' }} parado</span>
                </div>
                <p class="tt-muted tt-clamp2" style="margin:0">{{ $projeto->descricao }}</p>

                <div>
                    <h3 class="tt-sec-tit" style="margin-bottom:8px">Próximas tarefas</h3>
                    @if($projeto->proximas_tarefas->isNotEmpty())
                        <ul class="tt-lista-simples">
                            @foreach($projeto->proximas_tarefas as $tarefa)
                                <li>
                                    <span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}" style="font-size:0"></span>
                                    <span style="min-width:0;flex:1" title="{{ $tarefa->titulo }}">{{ \Illuminate\Support\Str::limit($tarefa->titulo, 60) }}</span>
                                    <span class="tt-muted" style="font-size:12.5px;white-space:nowrap">{{ $tarefa->prioridade }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="tt-muted" style="margin:0;font-style:italic">Sem tarefas pendentes.</p>
                    @endif
                </div>

                <a class="tt-btn bloco" style="margin-top:auto" href="{{ route('treetask.lista', $projeto->id_projeto) }}">Ir para o projeto</a>
            </article>
        @empty
            <div class="tt-vazio" style="grid-column:1/-1">Você está em dia com todos os seus projetos!</div>
        @endforelse
    </section>
</x-TreeTask::layout>
