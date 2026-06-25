@extends('Alfred::layouts.app')

@section('title', 'Configurações - Alfred')

@section('content')
<div class="card">
    <div class="page-header">
        <h2>⚙️ Configurações</h2>
        <a href="{{ route('alfred.admin.index') }}" class="btn btn-secondary">← Voltar</a>
    </div>

    <form action="{{ route('alfred.admin.configuracoes.update') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label>💧 Meta de Água Diária (ml)</label>
            <input type="number" name="meta_agua" value="{{ $configuracoes['meta_agua'] }}" min="500" max="5000" step="100" class="form-control" style="font-size: 1.25rem;">
            <small style="color: var(--text-muted);">Quantidade de água recomendada por dia (padrão: 2000ml)</small>
        </div>
        
        <button type="submit" class="btn btn-success">Salvar Configurações</button>
    </form>
</div>
@endsection
