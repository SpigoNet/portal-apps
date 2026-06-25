@extends('Alfred::layouts.app')

@section('title', 'Início - Alfred')

@section('content')
<div class="card" style="background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-purple) 100%); border: none;">
    <h2 style="color: white; margin-bottom: 4px;">👋 Bom dia!</h2>
    <p style="opacity: 0.9; font-size: 0.9375rem; color: white;">Vamos cuidar de você hoje</p>
</div>

<div class="btn-group">
    <a href="{{ route('alfred.rotinas.index') }}" class="btn btn-success">
        <span>🔄</span> Ver Rotinas
    </a>
    <a href="{{ route('alfred.tarefas.index') }}" class="btn">
        <span>📋</span> Tarefas
    </a>
</div>

@if(!$diaUtil)
    <div class="alert alert-info">
        <span>🌟</span>
        <div>
            <strong>Fim de semana</strong>
            <br>
            <small>Prioridade para rotinas pessoais</small>
        </div>
    </div>
@endif

<div class="card">
    <div class="flex-between mb-2">
        <h2>🎯 Top 3 Tarefas</h2>
        <span class="badge" style="background: var(--accent-green); color: white;">🟢</span>
    </div>
    
    @if($tarefasTop3->count() > 0)
        <div class="list-group">
            @foreach($tarefasTop3 as $tarefa)
                <div class="list-item" style="border-left: 4px solid {{ $tarefa->prioridade >= 3 ? '#ef4444' : ($tarefa->prioridade == 2 ? '#f59e0b' : '#4361ee') }};">
                    <div style="font-weight: 600; margin-bottom: 4px;">
                        {{ $tarefa->titulo }}
                    </div>
                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 4px;">
                        📁 {{ $tarefa->nome_projeto }}
                    </div>
                    <div class="flex-between">
                        <span style="font-size: 0.75rem; color: var(--text-muted);">
                            📅 {{ $tarefa->prazo ? $tarefa->prazo->format('d/m') : 'Sem prazo' }}
                        </span>
                        @if($tarefa->prioridade >= 3)
                            <span class="badge badge-alta">{{ $tarefa->prioridade_original }}</span>
                        @elseif($tarefa->prioridade == 2)
                            <span class="badge badge-media">Média</span>
                        @else
                            <span class="badge badge-baixa">Baixa</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🎉</div>
            <p>Nenhuma tarefa urgente!</p>
            <small>Aproveite o dia</small>
        </div>
    @endif
</div>

@if($rotinasHoje->count() > 0)
    <div class="card" style="border: 2px solid var(--accent-purple);">
        <div class="flex-between mb-2">
            <h2>🔄 Rotinas de Hoje</h2>
            <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: var(--accent-purple);">
                {{ $rotinasPendentes->count() }} pendentes
            </span>
        </div>
        
        <div class="list-group">
            @foreach($rotinasHoje as $rotina)
                <div class="list-item" style="border-left: 4px solid {{ $rotina->categoria_badge['cor'] }}; {{ $rotina->executada_hoje ? 'opacity: 0.6;' : '' }}">
                    <div class="flex-between" style="margin-bottom: 4px;">
                        <div style="font-weight: 600; {{ $rotina->executada_hoje ? 'text-decoration: line-through;' : '' }}">
                            {{ $rotina->titulo }}
                            @if($rotina->executada_hoje)
                                <span style="color: var(--accent-green);">✓</span>
                            @elseif($rotina->pulada_hoje)
                                <span style="color: var(--accent-orange);">⏭</span>
                            @endif
                        </div>
                        <span class="badge" style="background: {{ $rotina->categoria_badge['cor'] }}; color: white; font-size: 0.7rem;">
                            {{ $rotina->categoria_badge['label'] }}
                        </span>
                    </div>
                    
                    @if($rotina->descricao)
                        <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 4px;">
                            {{ Str::limit($rotina->descricao, 60) }}
                        </div>
                    @endif
                    
                    <div class="flex-between" style="font-size: 0.75rem; color: var(--text-muted);">
                        <span>
                            @if($rotina->horario_sugerido)
                                🕐 {{ $rotina->horario_sugerido }}
                            @else
                                🕐 Sem horário
                            @endif
                        </span>
                        <span>{{ $rotina->descricao_recorrencia }}</span>
                    </div>
                    
                    @if($rotina->pulada_hoje && $rotina->motivo_pulo)
                        <div style="font-size: 0.75rem; color: var(--accent-orange); margin-top: 4px;">
                            💭 {{ $rotina->motivo_pulo }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <a href="{{ route('alfred.rotinas.index') }}" class="btn btn-success mt-2">
            Ver Todas as Rotinas
        </a>
    </div>
@endif

<div class="card">
    <div class="flex-between mb-2">
        <h2>💧 Hidratação</h2>
        <span style="font-weight: 600; color: var(--accent-blue);">{{ $progressoAgua['percentual'] }}%</span>
    </div>
    
    <div class="progress-bar mb-2">
        <div class="progress-fill" style="width: {{ $progressoAgua['percentual'] }}%;">
            {{ $progressoAgua['consumido'] }}ml
        </div>
    </div>
    
    <div class="text-center mb-2" style="font-size: 0.9375rem; color: var(--text-muted);">
        Meta: {{ $progressoAgua['meta'] }}ml
        @if($progressoAgua['restante'] > 0)
            <br><small>Faltam {{ $progressoAgua['restante'] }}ml</small>
        @else
            <br><span style="color: var(--accent-green); font-weight: 600;">🎉 Meta atingida!</span>
        @endif
    </div>
    
    <form action="{{ route('alfred.hidratacao.registrar-padrao') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">
            <span style="font-size: 1.25rem;">💧</span> +250ml
        </button>
    </form>
</div>

@if($medicamentosAlerta->count() > 0)
    <div class="card" style="border: 2px solid var(--accent-red);">
        <h2 style="color: var(--accent-red); margin-bottom: 12px;">💊 Medicamentos</h2>
        
        <div class="list-group">
            @foreach($medicamentosAlerta as $medicamento)
                <div class="list-item" style="border-left: 4px solid var(--accent-red);">
                    <div class="flex-between">
                        <div>
                            <div style="font-weight: 600;">{{ $medicamento->nome }}</div>
                            <small style="color: var(--accent-red);">
                                {{ $medicamento->estoque_atual }} unidades
                            </small>
                        </div>
                        @if($medicamento->estoque_atual <= 0)
                            <span class="badge" style="background: var(--accent-red); color: white;">🚨</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <a href="{{ route('alfred.medicamentos.index') }}" class="btn btn-danger mt-2">
            Ver Medicamentos
        </a>
    </div>
@endif

<div class="card">
    <h2 class="mb-2">⚡ Como está sua energia?</h2>
    <form action="{{ route('alfred.energia.atualizar') }}" method="POST">
        @csrf
        <div class="btn-group">
            <button type="submit" name="energia" value="baixa" 
                class="btn {{ $energia == 'baixa' ? 'btn-danger' : 'btn-secondary' }}">
                <span style="font-size: 1.25rem;">🔴</span>
                <div style="font-size: 0.75rem;">Baixa</div>
            </button>
            <button type="submit" name="energia" value="media" 
                class="btn {{ $energia == 'media' ? 'btn-warning' : 'btn-secondary' }}">
                <span style="font-size: 1.25rem;">🟡</span>
                <div style="font-size: 0.75rem;">Média</div>
            </button>
            <button type="submit" name="energia" value="alta" 
                class="btn {{ $energia == 'alta' ? 'btn-success' : 'btn-secondary' }}">
                <span style="font-size: 1.25rem;">🟢</span>
                <div style="font-size: 0.75rem;">Alta</div>
            </button>
        </div>
    </form>
</div>

@if($totalDiasRuim > 0)
    <div class="card" style="background: var(--bg-tertiary); border: 1px dashed var(--border-color);">
        <p class="text-center" style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            📊 Este mês: {{ $totalDiasRuim }} dia(s) de baixa energia<br>
            <small>🫂 Cuidar de você é prioridade</small>
        </p>
    </div>
@endif

<div class="text-center mt-3" style="padding-top: 16px; border-top: 1px solid var(--border-color);">
    <a href="{{ route('alfred.admin.index') }}" class="btn btn-secondary">
        ⚙️ Área Administrativa
    </a>
</div>
@endsection
