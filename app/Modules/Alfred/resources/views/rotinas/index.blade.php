@extends('Alfred::layouts.app')

@section('title', 'Rotinas - Alfred')

@section('content')
<div class="card">
    <div class="page-header">
        <h2>🔄 Minhas Rotinas</h2>
        <div class="page-actions">
            <a href="{{ route('alfred.admin.rotinas') }}" class="btn btn-secondary">⚙️ Gerenciar</a>
            <a href="{{ route('alfred.rotinas.calendario') }}" class="btn btn-purple">
                📅 Calendário
            </a>
        </div>
    </div>

    <!-- Navegação de Data -->
    <div class="flex-center" style="margin-bottom: 24px; gap: 16px;">
        <a href="{{ route('alfred.rotinas.index', ['data' => $dataOntem->format('Y-m-d')]) }}" class="btn btn-sm btn-secondary">
            ⬅️ Anterior
        </a>
        <div style="font-weight: 600; font-size: 1.1rem; min-width: 130px; text-align: center;">
            {{ $dataSelecionada->isToday() ? 'Hoje' : $dataSelecionada->format('d/m/Y') }}
        </div>
        <a href="{{ route('alfred.rotinas.index', ['data' => $dataAmanha->format('Y-m-d')]) }}" class="btn btn-sm btn-secondary">
            Próximo ➡️
        </a>
    </div>
    
    @if($rotinasHoje->count() > 0)
        <div class="card" style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid var(--accent-green);">
            <h3 style="color: var(--accent-green); margin-bottom: 10px;">📅 Rotinas para {{ $dataSelecionada->isToday() ? 'Hoje' : $dataSelecionada->format('d/m') }}</h3>
            
            @php
                $feitas = $rotinasHoje->where('executada_hoje', true);
                $puladas = $rotinasHoje->where('pulada_hoje', true);
            @endphp
            
            @if($pendentesHoje->count() > 0)
                <p style="color: var(--text-muted); margin-bottom: 16px;">
                    {{ $pendentesHoje->count() }} pendente(s) | {{ $feitas->count() }} feita(s) | {{ $puladas->count() }} pulada(s)
                </p>
            @endif
            
            <div class="list-group">
                @foreach($rotinasHoje as $rotina)
                    <div class="list-item" style="border-left: 4px solid {{ $rotina->categoria_badge['cor'] }};">
                        <div class="flex-between" style="margin-bottom: 8px;">
                            <div class="flex-start">
                                @if($rotina->executada_hoje)
                                    <span style="font-size: 1.25rem;">✅</span>
                                @elseif($rotina->pulada_hoje)
                                    <span style="font-size: 1.25rem;">⏭️</span>
                                @else
                                    <span style="font-size: 1.25rem;">⏳</span>
                                @endif
                                <span style="font-weight: 600; {{ $rotina->executada_hoje || $rotina->pulada_hoje ? 'text-decoration: line-through; opacity: 0.6;' : '' }}">
                                    {{ $rotina->titulo }}
                                </span>
                            </div>
                            <span class="badge" style="background: {{ $rotina->categoria_badge['cor'] }}; color: white;">
                                {{ $rotina->categoria_badge['label'] }}
                            </span>
                        </div>
                        
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 8px;">
                            🕐 {{ $rotina->descricao_recorrencia }}
                            @if($rotina->horario_sugerido)
                                | ⏰ {{ substr($rotina->horario_sugerido, 0, 5) }}
                            @endif
                        </div>
                        
                        @if($rotina->pulada_hoje && $rotina->motivo_pulo)
                            <div style="font-size: 0.8125rem; color: var(--accent-orange); margin-bottom: 8px;">
                                💭 Motivo: {{ $rotina->motivo_pulo }}
                            </div>
                        @endif
                        
                        <div class="btn-group">
                            @if(!$rotina->executada_hoje && !$rotina->pulada_hoje)
                                <form action="{{ route('alfred.rotinas.marcar-executada', $rotina) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $dataSelecionada->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-success">✓ Concluir</button>
                                </form>
                                <button type="button" class="btn btn-secondary" onclick="mostrarModalPulo({{ $rotina->id }}, '{{ addslashes($rotina->titulo) }}', '{{ $dataSelecionada->format('Y-m-d') }}')">
                                    ⏭ Pular
                                </button>
                            @elseif($rotina->executada_hoje)
                                <form action="{{ route('alfred.rotinas.desfazer-execucao', $rotina) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="data" value="{{ $dataSelecionada->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-secondary">↩ Desfazer</button>
                                </form>
                            @elseif($rotina->pulada_hoje)
                                <form action="{{ route('alfred.rotinas.desfazer-pulo', $rotina) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="data" value="{{ $dataSelecionada->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-warning">↩ Desfazer</button>
                                </form>
                                <form action="{{ route('alfred.rotinas.marcar-executada', $rotina) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $dataSelecionada->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-success">✓ Concluir</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p>Nenhuma rotina para {{ $dataSelecionada->isToday() ? 'hoje' : $dataSelecionada->format('d/m/Y') }}.</p>
        </div>
    @endif
</div>

<div class="card" style="opacity: 0.8;">
    <h3 class="section-title">🌅 Rotinas para {{ $dataAmanha->isTomorrow() ? 'Amanhã' : $dataAmanha->format('d/m') }}</h3>
    
    @if($rotinasAmanha->count() > 0)
        <div class="list-group">
            @foreach($rotinasAmanha as $rotina)
                <div class="list-item" style="border-left: 4px solid {{ $rotina->categoria_badge['cor'] }}; padding: 12px;">
                    <div class="flex-between">
                        <div class="flex-start">
                            <span style="font-size: 1.1rem; margin-right: 8px;">⏳</span>
                            <span style="font-weight: 500;">
                                {{ $rotina->titulo }}
                            </span>
                        </div>
                        @if($rotina->horario_sugerido)
                            <span style="font-size: 0.8125rem; color: var(--text-muted);">
                                ⏰ {{ substr($rotina->horario_sugerido, 0, 5) }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state" style="padding: 20px;">
            <p style="margin: 0; font-size: 0.9rem;">Nenhuma rotina agendada.</p>
        </div>
    @endif
</div>

<div id="modalPulo" class="modal-overlay">
    <div class="modal-content">
        <h3 class="mb-2">⏭️ Pular Rotina</h3>
        <p style="color: var(--text-muted); margin-bottom: 16px;">
            Você está pulando: <strong id="tituloRotinaPulo"></strong>
        </p>
        
        <form id="formPular" method="POST" action="">
            @csrf
            <input type="hidden" name="data" id="dataRotinaPulo" value="">
            <div class="form-group">
                <label for="motivo">Motivo (opcional):</label>
                <textarea name="motivo" id="motivo" class="form-control" rows="3" 
                          placeholder="Ex: Não estou no clima hoje, estou cansado..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn btn-success">Confirmar</button>
                <button type="button" class="btn btn-secondary" onclick="fecharModalPulo()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function mostrarModalPulo(rotinaId, titulo, data) {
        document.getElementById('tituloRotinaPulo').textContent = titulo;
        document.getElementById('formPular').action = '/rotinas/' + rotinaId + '/pular';
        document.getElementById('dataRotinaPulo').value = data;
        document.getElementById('modalPulo').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function fecharModalPulo() {
        document.getElementById('modalPulo').style.display = 'none';
        document.body.style.overflow = '';
    }
    
    document.getElementById('modalPulo').addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalPulo();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModalPulo();
        }
    });
</script>
@endsection
