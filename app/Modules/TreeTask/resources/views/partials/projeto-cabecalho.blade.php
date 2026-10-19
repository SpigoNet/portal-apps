{{-- Cabeçalho das visões de um projeto (lista, kanban e árvore). Recebe $projeto (com fases.tarefas) e $aba. --}}
@php
    $cor = \App\Modules\TreeTask\Support\Aparencia::corProjeto($projeto->id_projeto);
@endphp
<nav class="tt-crumb" aria-label="Trilha">
    <a href="{{ route('treetask.index') }}">Projetos</a>
    <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
    <b>{{ $projeto->nome }}</b>
</nav>

<header class="tt-ph" style="align-items:flex-start">
    <div style="display:flex;gap:16px;align-items:flex-start;min-width:0;flex:1 1 420px">
        <div class="tt-tile g" style="background: {{ $cor }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($projeto->nome, 0, 1)) }}</div>
        <div style="min-width:0">
            <h1 class="tt-h1" style="font-size:30px">{{ $projeto->nome }}</h1>
            <p class="tt-muted" style="margin:4px 0 0;max-width:620px">{{ $projeto->descricao }}</p>
        </div>
    </div>
    <div class="tt-acoes">
        <a class="tt-btn" href="{{ route('treetask.ai.index', ['type' => 'project', 'id' => $projeto->id_projeto]) }}">
            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>Assistente IA
        </a>
        <a class="tt-btn pri" href="{{ route('treetask.kanban.nova_tarefa', ['projeto' => $projeto->id_projeto]) }}">
            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Nova tarefa
        </a>
    </div>
</header>

<div class="tt-seg" role="group" aria-label="Visualização" style="align-self:flex-start">
    <a href="{{ route('treetask.lista', $projeto->id_projeto) }}" @class(['on' => $aba === 'lista'])>Lista</a>
    <a href="{{ route('treetask.kanban', ['projeto' => [$projeto->id_projeto]]) }}" @class(['on' => $aba === 'kanban'])>Kanban</a>
    <a href="{{ route('treetask.tree.view', $projeto->id_projeto) }}" @class(['on' => $aba === 'arvore'])>Árvore</a>
</div>
