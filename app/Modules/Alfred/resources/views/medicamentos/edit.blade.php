@extends('Alfred::layouts.app')

@section('title', 'Editar Medicamento - Alfred')

@section('content')
<div class="card">
    <h2 class="mb-2">✏️ Editar Medicamento</h2>
    
    <div class="card" style="background: var(--bg-tertiary); text-align: center; padding: 24px;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Estoque Atual</div>
        <div style="font-size: 3rem; font-weight: 700; color: {{ $medicamento->estoque_atual <= 0 ? 'var(--accent-red)' : ($medicamento->precisaComprar ? 'var(--accent-orange)' : 'var(--accent-green)') }};" id="stockDisplay">
            {{ $medicamento->estoque_atual }}
        </div>
        <div style="display: flex; justify-content: center; gap: 16px; margin-top: 16px;">
            <button type="button" class="btn btn-sm btn-danger" onclick="adjustStock(-1)">−</button>
            <button type="button" class="btn btn-sm btn-success" onclick="adjustStock(1)">+</button>
        </div>
    </div>
    
    <form action="{{ route('alfred.medicamentos.update', $medicamento) }}" method="POST" id="medicamentoForm">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="estoque_atual" id="estoque_atual" value="{{ $medicamento->estoque_atual }}">
        
        <div class="form-group">
            <label for="nome" class="required">Nome do Medicamento</label>
            <input type="text" name="nome" id="nome" class="form-control" required 
                   value="{{ $medicamento->nome }}">
        </div>

        <div class="form-group">
            <label for="ponto_recompra" class="required">Alertar quando chegar em</label>
            <input type="number" name="ponto_recompra" id="ponto_recompra" class="form-control" required 
                   min="1" value="{{ $medicamento->ponto_recompra }}">
            <small style="color: var(--text-muted);">Você receberá um alerta quando o estoque atingir este número</small>
        </div>

        <div class="btn-group-vertical">
            <button type="submit" class="btn btn-success">💾 Salvar</button>
            <a href="{{ route('alfred.medicamentos.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <div class="divider"></div>

    <div class="card" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--accent-red);">
        <h3 style="color: var(--accent-red); margin-bottom: 8px;">🗑️ Zona de Perigo</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 16px;">Esta ação não pode ser desfeita.</p>
        
        <form action="{{ route('alfred.medicamentos.destroy', $medicamento) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" style="width: 100%;">Excluir Medicamento</button>
        </form>
    </div>
</div>

<script>
    let currentStock = parseInt('{{ $medicamento->estoque_atual }}');
    let alertPoint = parseInt('{{ $medicamento->precisaComprar ? $medicamento->ponto_recompra : 0 }}');
    
    function adjustStock(delta) {
        currentStock = Math.max(0, currentStock + delta);
        document.getElementById('estoque_atual').value = currentStock;
        document.getElementById('stockDisplay').textContent = currentStock;
        
        const display = document.getElementById('stockDisplay');
        display.style.color = currentStock <= 0 ? 'var(--accent-red)' : (currentStock <= alertPoint ? 'var(--accent-orange)' : 'var(--accent-green)');
    }
</script>
@endsection
