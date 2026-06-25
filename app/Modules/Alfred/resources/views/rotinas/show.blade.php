@extends('Alfred::layouts.app')

@section('title', $rotina->titulo . ' - Alfred')

@section('content')
<div class="page-header">
    <h2>{{ $rotina->titulo }}</h2>
    <div class="page-actions">
        <a href="{{ route('alfred.rotinas.edit', $rotina) }}" class="btn btn-sm">✏️ Editar</a>
        <a href="{{ route('alfred.rotinas.index') }}" class="btn btn-sm btn-secondary">Voltar</a>
    </div>
</div>

<div class="card">
    @if($rotina->descricao)
        <p style="color: var(--text-secondary); margin-bottom: 16px;">{{ $rotina->descricao }}</p>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
            <small style="color: var(--text-muted);">Categoria</small>
            <div style="font-weight: 600;">{{ $rotina->categoria_badge['label'] }}</div>
        </div>
        <div>
            <small style="color: var(--text-muted);">Recorrência</small>
            <div style="font-weight: 600;">{{ $rotina->descricao_recorrencia }}</div>
        </div>
        <div>
            <small style="color: var(--text-muted);">Prioridade</small>
            <div style="font-weight: 600;">{{ $rotina->prioridade_label }}</div>
        </div>
        @if($rotina->horario_sugerido)
            <div>
                <small style="color: var(--text-muted);">Horário sugerido</small>
                <div style="font-weight: 600;">{{ $rotina->horario_sugerido }}</div>
            </div>
        @endif
    </div>
</div>

@if($rotina->execucoes->count() > 0)
    <div class="card">
        <h3>📊 Histórico</h3>
        <div class="list-group">
            @foreach($rotina->execucoes()->latest('data_execucao')->take(10)->get() as $execucao)
                <div class="list-item" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>{{ $execucao->data_execucao->format('d/m/Y') }}</span>
                    @if($execucao->observacao)
                        <small style="color: var(--text-muted);">{{ $execucao->observacao }}</small>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
