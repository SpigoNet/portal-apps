@extends('Alfred::layouts.app')

@section('title', 'Administração - Alfred')

@section('content')
<div class="card">
    <h2 class="mb-2">⚙️ Área Administrativa</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Gerencie categorias, configurações e dados do sistema.</p>
    
    <div style="display: grid; gap: 12px;">
        <a href="{{ route('alfred.admin.categorias-rotina') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-purple);">
            <div style="font-size: 2rem; margin-bottom: 8px;">🏷️</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Categorias de Rotinas</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">{{ $stats['total_categorias'] }} categorias</div>
        </a>
        
        <a href="{{ route('alfred.admin.rotinas') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-blue);">
            <div style="font-size: 2rem; margin-bottom: 8px;">🔄</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Gerenciar Rotinas</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">{{ $stats['rotinas_ativas'] }} rotinas ativas</div>
        </a>
        
        <a href="{{ route('alfred.admin.configuracoes') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-green);">
            <div style="font-size: 2rem; margin-bottom: 8px;">⚙️</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Configurações</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">{{ $stats['meta_agua'] ?? 2000 }}ml meta diária</div>
        </a>
        
        <a href="{{ route('alfred.medicamentos.index') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-red);">
            <div style="font-size: 2rem; margin-bottom: 8px;">💊</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Medicamentos</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">{{ $stats['total_medicamentos'] }} medicamentos</div>
        </a>
        
        <a href="{{ route('alfred.admin.personas.index') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-teal);">
            <div style="font-size: 2rem; margin-bottom: 8px;">🧑‍⚕️</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Personas</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">Gerencie personagens que interagem via WhatsApp</div>
        </a>

        <a href="{{ route('alfred.admin.agendamentos.index') }}" class="card" style="display: block; padding: 20px; text-decoration: none; border-left: 4px solid var(--accent-orange);">
            <div style="font-size: 2rem; margin-bottom: 8px;">⏰</div>
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">Agendamentos</div>
            <div style="font-size: 0.875rem; color: var(--text-muted);">Mensagens periódicas via WhatsApp por persona</div>
        </a>
    </div>
</div>
@endsection
