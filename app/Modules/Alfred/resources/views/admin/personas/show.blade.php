@extends('Alfred::layouts.app')

@section('title', 'Persona - Administração')

@section('content')
<div class="card">
    <h2>{{ $persona->name }}</h2>
    <a href="{{ route('alfred.admin.personas.index') }}" class="btn btn-secondary">← Voltar</a>

    <div style="margin-top:16px;">
        <p><strong>Slug:</strong> {{ $persona->slug }}</p>
        <p><strong>Canal:</strong> {{ $persona->canal === 'telegram' ? 'Telegram' : 'WhatsApp' }}</p>
        <p><strong>Grupo WhatsApp:</strong> {{ $persona->whatsapp_group_jid ?? '-' }}</p>
        <p><strong>Telegram Token:</strong> {{ $persona->telegram_token ? Str::limit($persona->telegram_token, 12).'…' : '-' }}</p>
        <p><strong>Telegram Chat ID:</strong> {{ $persona->telegram_chat_id ?? '-' }}</p>
        <p><strong>Ativo:</strong> {{ $persona->active ? 'Sim' : 'Não' }}</p>
        <p><strong>Personality:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;">{{ json_encode($persona->personality, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <p><strong>Metadata:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;">{{ json_encode($persona->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    @if($persona->canal === 'telegram')
    <div class="card" style="margin-top:24px;">
        <h3>Webhook do Telegram (respostas via Pidgey)</h3>

        <p><strong>Endereço padrão:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;white-space:pre-wrap;">{{ $webhookPadrao }}</pre>

        @if($webhookInfo)
        <p><strong>Status atual:</strong></p>
        <pre style="background:#f6f6f6;padding:8px;border-radius:4px;white-space:pre-wrap;">{{ json_encode($webhookInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <form action="{{ route('alfred.admin.personas.configure-webhook', $persona) }}" method="post">
                @csrf
                <input type="hidden" name="telegram_webhook_url" value="{{ $webhookPadrao }}">
                <button class="btn btn-primary">Configurar Webhook</button>
            </form>

            <form action="{{ route('alfred.admin.personas.clear-webhook', $persona) }}" method="post" onsubmit="return confirm('Remover o webhook do Telegram desta persona?');">
                @csrf
                <button class="btn btn-outline-danger">Remover Webhook</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
