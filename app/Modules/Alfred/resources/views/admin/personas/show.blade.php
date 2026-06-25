@extends('Alfred::layouts.app')

@section('title', 'Persona - Administração')

@section('content')
<div class="card">
    <h2>{{ $persona->name }}</h2>
    <a href="{{ route('alfred.admin.personas.index') }}" class="btn btn-secondary">← Voltar</a>

    <div style="margin-top:16px;">
        <p><strong>Slug:</strong> {{ $persona->slug }}</p>
        <p><strong>Grupo WhatsApp:</strong> {{ $persona->whatsapp_group_jid ?? '-' }}</p>
        <p><strong>Ativo:</strong> {{ $persona->active ? 'Sim' : 'Não' }}</p>
        <p><strong>Personality:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;">{{ json_encode($persona->personality, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <p><strong>Metadata:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;">{{ json_encode($persona->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
@endsection
