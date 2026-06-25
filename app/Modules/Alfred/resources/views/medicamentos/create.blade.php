@extends('Alfred::layouts.app')

@section('title', 'Novo Medicamento - Alfred')

@section('content')
<div class="card">
    <h2 class="mb-2">➕ Novo Medicamento</h2>
    
    <div class="alert alert-warning">
        <strong>⚠️ Importante</strong>
        <br>
        <small>Este sistema apenas controla estoque. Horários e dosagens devem ser definidos pelo seu médico.</small>
    </div>
    
    <form action="{{ route('alfred.medicamentos.store') }}" method="POST" id="medicamentoForm">
        @csrf
        
        <div class="form-group">
            <label for="nome" class="required">Nome do Medicamento</label>
            <input type="text" name="nome" id="nome" class="form-control" required 
                   placeholder="Ex: Paracetamol 500mg">
        </div>

        <div class="form-group">
            <label for="estoque_atual" class="required">Quantidade em Estoque</label>
            <input type="number" name="estoque_atual" id="estoque_atual" class="form-control" required 
                   min="0" placeholder="0" style="text-align: center; font-size: 1.5rem; font-weight: 600;">
            <div style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('estoque_atual').value=10">10</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('estoque_atual').value=15">15</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('estoque_atual').value=30">30</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('estoque_atual').value=60">60</button>
            </div>
        </div>

        <div class="form-group">
            <label for="ponto_recompra" class="required">Alertar quando chegar em</label>
            <input type="number" name="ponto_recompra" id="ponto_recompra" class="form-control" required 
                   min="1" value="5" style="text-align: center;">
            <small style="color: var(--text-muted); display: block; margin-top: 6px;">Você receberá um alerta quando o estoque atingir este número</small>
        </div>

        <div class="btn-group-vertical">
            <button type="submit" class="btn btn-success">💊 Cadastrar</button>
            <a href="{{ route('alfred.medicamentos.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
