@extends('Alfred::layouts.app')

@section('title', 'Hidratação - Alfred')

@section('content')
<div class="card">
    <h2>💧 Hidratação</h2>
    
    <div class="text-center mb-3">
        <div style="font-size: 48px; margin-bottom: 10px;">
            @if($progresso['percentual'] >= 100)
                🎉
            @elseif($progresso['percentual'] >= 75)
                💧
            @elseif($progresso['percentual'] >= 50)
                💦
            @else
                🥤
            @endif
        </div>
        
        <div class="progress-bar" style="max-width: 400px; margin: 0 auto 20px;">
            <div class="progress-fill" style="width: {{ $progresso['percentual'] }}%;">
                {{ $progresso['percentual'] }}%
            </div>
        </div>
        
        <p style="font-size: 24px; font-weight: bold; margin-bottom: 10px;">
            {{ $progresso['consumido'] }}ml / {{ $progresso['meta'] }}ml
        </p>
        
        @if($progresso['restante'] > 0)
            <p style="color: var(--text-muted);">Faltam {{ $progresso['restante'] }}ml para atingir a meta</p>
        @else
            <p style="color: var(--accent-green); font-weight: bold;">🎉 Meta diária atingida! Parabéns!</p>
        @endif
    </div>

    <h3 class="section-title">Registrar Consumo</h3>
    <div class="btn-group">
        <form action="{{ route('alfred.hidratacao.registrar-padrao') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success">
                +250ml (Copo)
            </button>
        </form>
        
        <form action="{{ route('alfred.hidratacao.store') }}" method="POST">
            @csrf
            <input type="hidden" name="quantidade_ml" value="500">
            <button type="submit" class="btn">
                +500ml (Garrafa)
            </button>
        </form>
    </div>

    @if($historicoHoje->count() > 0)
        <div class="divider"></div>
        <h3 class="mb-2">📊 Histórico de Hoje</h3>
        <div class="list-group">
            @foreach($historicoHoje as $consumo)
                <div class="list-item" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>{{ $consumo->created_at->format('H:i') }}</span>
                    <span style="font-weight: 600; color: var(--accent-blue);">{{ $consumo->quantidade_ml }}ml</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
