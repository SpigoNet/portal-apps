@php
    $isStalled = $tarefa->status == 'andamento' && isset($tarefa->iniciada_em) && $tarefa->iniciada_em && $tarefa->iniciada_em->diffInHours(now()) > 24;
@endphp
<div class="list-item" style="border-left: 4px solid {{ $isStalled ? 'var(--accent-red)' : 'var(--accent-blue)' }};">
    <div class="flex-between" style="margin-bottom: 8px;">
        <div style="font-weight: 600; {{ $isStalled ? 'color: var(--accent-red);' : '' }}">
            @if($isStalled)
                ⚠️ Apenas abrir:
            @endif
            {{ $tarefa->titulo }}
        </div>
        @if($isStalled)
            <span class="badge badge-alta">Parada {{ $tarefa->iniciada_em->diffInHours(now()) }}h</span>
        @endif
    </div>
    
    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 8px;">
        <span>📁 {{ $tarefa->nome_projeto }}</span>
        <span>📂 {{ $tarefa->nome_fase }}</span>
        <span>📅 {{ $tarefa->prazo ? $tarefa->prazo->format('d/m/Y') : 'Sem prazo' }}</span>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
        <form action="{{ route('alfred.tarefas.atualizar-prioridade', $tarefa->id_tarefa) }}" method="POST" style="display: flex; gap: 8px; align-items: center;">
            @csrf
            <label style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">Prioridade:</label>
            <select name="prioridade" onchange="this.form.submit()" class="form-control" style="min-height: 36px; padding: 6px 12px; flex: 1;">
                <option value="4" {{ $tarefa->prioridade == 4 ? 'selected' : '' }}>🔴 Urgente</option>
                <option value="3" {{ $tarefa->prioridade == 3 ? 'selected' : '' }}>🟠 Alta</option>
                <option value="2" {{ $tarefa->prioridade == 2 ? 'selected' : '' }}>🟡 Média</option>
                <option value="1" {{ $tarefa->prioridade == 1 ? 'selected' : '' }}>⚪ Baixa</option>
            </select>
        </form>

        <form action="{{ route('alfred.tarefas.atualizar-status', $tarefa->id_tarefa) }}" method="POST" style="display: flex; gap: 8px; align-items: center;">
            @csrf
            <label style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">Status:</label>
            <select name="status" onchange="this.form.submit()" class="form-control" style="min-height: 36px; padding: 6px 12px; flex: 1;">
                <option value="A Fazer" {{ $tarefa->status == 'pendente' ? 'selected' : '' }}>⏳ A Fazer</option>
                <option value="Planejamento" {{ $tarefa->status == 'pendente' && $tarefa->status_original == 'Planejamento' ? 'selected' : '' }}>📋 Planejamento</option>
                <option value="Em Andamento" {{ $tarefa->status == 'andamento' ? 'selected' : '' }}>🔄 Em Andamento</option>
                <option value="Aguardando resposta" {{ $tarefa->status_original == 'Aguardando resposta' ? 'selected' : '' }}>⏸️ Aguardando</option>
                <option value="Concluído" {{ $tarefa->status == 'concluida' ? 'selected' : '' }}>✅ Concluído</option>
            </select>
        </form>
    </div>
</div>
