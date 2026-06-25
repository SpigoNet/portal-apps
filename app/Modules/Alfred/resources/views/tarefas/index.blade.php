@extends('Alfred::layouts.app')

@section('title', 'Tarefas - Alfred')

@section('content')
<div class="card">
    <div class="page-header">
        <h2>📋 Tarefas do TreeTask</h2>
        <span style="color: var(--accent-green); font-weight: 500;">🟢 Conectado</span>
    </div>

    <div class="alert alert-info">
        <div>
            <strong>💡 Dica:</strong> Você pode ordenar e editar prioridade/status diretamente aqui!
        </div>
    </div>

    @if($tarefas->count() > 0)
        <div class="card" style="background: var(--bg-tertiary); padding: 12px;">
            <div class="flex-between" style="flex-wrap: wrap; gap: 12px;">
                <div class="flex-start" style="gap: 8px; flex-wrap: wrap;">
                    <select onchange="window.location.href='{{ route('alfred.tarefas.index') }}?ordenar='+this.value+'&agrupar={{ $agruparPor }}'" class="form-control" style="min-height: 36px; padding: 6px 12px; font-size: 0.875rem;">
                        <option value="prioridade" {{ $ordenarPor == 'prioridade' ? 'selected' : '' }}>Ordenar: Prioridade</option>
                        <option value="data" {{ $ordenarPor == 'data' ? 'selected' : '' }}>Ordenar: Data</option>
                        <option value="projeto" {{ $ordenarPor == 'projeto' ? 'selected' : '' }}>Ordenar: Projeto</option>
                    </select>
                    <select onchange="window.location.href='{{ route('alfred.tarefas.index') }}?ordenar={{ $ordenarPor }}&agrupar='+this.value" class="form-control" style="min-height: 36px; padding: 6px 12px; font-size: 0.875rem;">
                        <option value="nenhum" {{ $agruparPor == 'nenhum' ? 'selected' : '' }}>Agrupar: Nenhum</option>
                        <option value="projeto" {{ $agruparPor == 'projeto' ? 'selected' : '' }}>Agrupar: Projeto</option>
                        <option value="fase" {{ $agruparPor == 'fase' ? 'selected' : '' }}>Agrupar: Fase</option>
                    </select>
                </div>
                <span style="font-size: 0.8125rem; color: var(--text-muted);">{{ $tarefas->count() }} tarefa(s)</span>
            </div>
        </div>

        @if($agruparPor != 'nenhum' && $tarefasAgrupadas)
            @foreach($tarefasAgrupadas as $grupo => $tarefasDoGrupo)
                <div style="background: var(--header-bg); color: white; padding: 12px 16px; border-radius: 12px; margin: 16px 0 12px 0;">
                    <h3 style="color: white; margin: 0; font-size: 1rem;">📁 {{ $grupo }} ({{ $tarefasDoGrupo->count() }})</h3>
                </div>
                @foreach($tarefasDoGrupo as $tarefa)
                    @include('Alfred::tarefas.partials.task-card', ['tarefa' => $tarefa])
                @endforeach
            @endforeach
        @else
            @foreach($tarefas as $tarefa)
                @include('Alfred::tarefas.partials.task-card', ['tarefa' => $tarefa])
            @endforeach
        @endif
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p>Nenhuma tarefa encontrada no TreeTask.</p>
        </div>
    @endif

    <div class="mt-3 text-center">
        <a href="{{ route('treetask.index') }}" target="_blank" class="btn">
            🌐 Abrir TreeTask
        </a>
    </div>
</div>
@endsection
