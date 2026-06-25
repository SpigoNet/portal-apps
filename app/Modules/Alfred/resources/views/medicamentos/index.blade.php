@extends('Alfred::layouts.app')

@section('title', 'Medicamentos - Alfred')

@section('content')
<div class="card">
    <div class="page-header">
        <h2>💊 Medicamentos</h2>
        <a href="{{ route('alfred.medicamentos.create') }}" class="btn btn-success">+ Novo</a>
    </div>

    @if($alertas->count() > 0)
        <div class="alert alert-warning">
            <div>
                <strong>⚠️ Alertas de Estoque</strong>
                @foreach($alertas as $medicamento)
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid rgba(0,0,0,0.1); margin-top: 8px;">
                        <span>{{ $medicamento->nome }}</span>
                        <span style="font-weight: 700; {{ $medicamento->estoque_atual <= 0 ? 'color: var(--accent-red);' : 'color: var(--accent-orange);' }}">
                            {{ $medicamento->estoque_atual }} unidades
                            @if($medicamento->estoque_atual <= 0) 🚨 @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $total = $medicamentos->count();
        $criticos = $medicamentos->where('estoque_atual', '<=', 0)->count();
        $alerta = $medicamentos->where('precisaComprar', true)->where('estoque_atual', '>', 0)->count();
        $ok = $total - $criticos - $alerta;
    @endphp

    @if($total > 0)
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
            <div class="card" style="text-align: center; padding: 16px 8px;">
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--accent-green);">{{ $ok }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">OK</div>
            </div>
            <div class="card" style="text-align: center; padding: 16px 8px;">
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--accent-orange);">{{ $alerta }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Alerta</div>
            </div>
            <div class="card" style="text-align: center; padding: 16px 8px;">
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--accent-red);">{{ $criticos }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Crítico</div>
            </div>
        </div>

        <h3 class="section-title">Meus Medicamentos</h3>

        <div class="list-group">
            @foreach($medicamentos as $medicamento)
                @php
                    $status = 'ok';
                    if ($medicamento->estoque_atual <= 0) {
                        $status = 'critical';
                    } elseif ($medicamento->precisaComprar) {
                        $status = 'warning';
                    }
                    $jaTomouHoje = in_array($medicamento->id, $medicamentosTomadosHoje);
                @endphp
                <div class="list-item" style="border-left: 4px solid {{ $status == 'critical' ? 'var(--accent-red)' : ($status == 'warning' ? 'var(--accent-orange)' : 'var(--accent-green)') }};">
                    <div class="flex-between" style="margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 600;">{{ $medicamento->nome }}</div>
                            @if($jaTomouHoje)
                                <span class="badge" style="background: var(--accent-green); color: white;">✓ Tomado hoje</span>
                            @elseif($status == 'critical')
                                <span class="badge badge-alta">🚨 Esgotado</span>
                            @elseif($status == 'warning')
                                <span class="badge badge-media">⚠️ Estoque Baixo</span>
                            @else
                                <span class="badge" style="background: var(--accent-green); color: white;">✓ Estoque OK</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-between" style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 12px;">
                        <span>Em Estoque: <strong style="color: {{ $status == 'critical' ? 'var(--accent-red)' : ($status == 'warning' ? 'var(--accent-orange)' : 'var(--accent-green)') }}; font-size: 1.25rem;">{{ $medicamento->estoque_atual }}</strong></span>
                        <span>Alerta em: {{ $medicamento->ponto_recompra }}</span>
                    </div>

                    <div class="btn-group">
                        @if($jaTomouHoje)
                            <form action="{{ route('alfred.medicamentos.desfazer', $medicamento) }}" method="POST" style="flex: 1;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">↩ Desfazer</button>
                            </form>
                        @else
                            <form action="{{ route('alfred.medicamentos.tomar', $medicamento) }}" method="POST" style="flex: 2;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="width: 100%;">✓ Tomei</button>
                            </form>
                        @endif
                        <a href="{{ route('alfred.medicamentos.edit', $medicamento) }}" class="btn btn-sm" style="flex: 1;">✏️</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">💊</div>
            <p>Nenhum medicamento cadastrado</p>
            <a href="{{ route('alfred.medicamentos.create') }}" class="btn btn-success mt-2">+ Cadastrar</a>
        </div>
    @endif
</div>
@endsection
