@extends('Alfred::layouts.app')

@section('title', 'Editar Persona - Administração')

@section('content')
<div class="card">
    <h2>Editar Persona</h2>
    <a href="{{ route('alfred.admin.personas.index') }}" class="btn btn-secondary">← Voltar</a>

    <form action="{{ route('alfred.admin.personas.update', $persona) }}" method="post" style="margin-top:16px; max-width:800px;">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="name" value="{{ old('name', $persona->name) }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $persona->slug) }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Canal de envio</label>
            <select name="canal" class="form-control" required>
                <option value="whatsapp" {{ old('canal', $persona->canal ?? 'whatsapp') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                <option value="telegram" {{ old('canal', $persona->canal ?? 'whatsapp') == 'telegram' ? 'selected' : '' }}>Telegram</option>
            </select>
        </div>

        <div class="form-group">
            <label>WhatsApp Group JID</label>
            <input type="text" name="whatsapp_group_jid" value="{{ old('whatsapp_group_jid', $persona->whatsapp_group_jid) }}" class="form-control">
        </div>

        <fieldset style="border:1px solid #eee; padding:12px 16px; border-radius:6px; margin-bottom:16px;">
            <legend style="font-weight:600; padding:0 6px;">Configurações do Bot Telegram</legend>

            <div class="form-group">
                <label>Token do Bot</label>
                <input type="text" name="telegram_token" value="{{ old('telegram_token', $persona->telegram_token) }}" class="form-control">
            </div>

            <div class="form-group">
                <label>Chat ID</label>
                <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $persona->telegram_chat_id) }}" class="form-control">
                <small style="color:var(--text-muted);">ID do chat, grupo ou canal onde a mensagem será enviada.</small>
            </div>
        </fieldset>

        <div class="form-group">
            <label>Personality (JSON)</label>
            <textarea name="personality" class="form-control" rows="6">{{ old('personality', json_encode($persona->personality, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
        </div>

        <div class="form-group">
            <label>Metadata (JSON)</label>
            <textarea name="metadata" class="form-control" rows="4">{{ old('metadata', json_encode($persona->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="active" value="1" {{ $persona->active ? 'checked' : '' }}> Ativo</label>
        </div>

        <div style="display:flex; gap:8px;">
            <button class="btn btn-primary">Salvar</button>
        </div>
    </form>

    @if($persona->canal === 'telegram')
    <fieldset style="border:1px solid #eee; padding:12px 16px; border-radius:6px; margin-top:24px;">
        <legend style="font-weight:600; padding:0 6px;">Webhook do Telegram (respostas via Pidgey)</legend>

        <p style="color:var(--text-muted); margin-top:0;">
            Configure o bot para enviar as mensagens recebidas ao gerenciador de webhook do módulo Pidgey,
            que gera uma resposta como a persona. O endereço padrão já inclui o slug da persona.
        </p>

        <div class="form-group">
            <label>Endereço do Webhook</label>
            <input type="text" name="telegram_webhook_url" value="{{ old('telegram_webhook_url', $webhookPadrao) }}" class="form-control" readonly
                   onclick="this.removeAttribute('readonly'); this.select();">
            <small style="color:var(--text-muted);">Padrão do módulo Pidgey com o parâmetro da persona ({{ $persona->slug }}). Clique para editar se precisar de uma URL customizada.</small>
        </div>

        @if($webhookInfo)
        <div class="form-group">
            <label>Status atual</label>
            <pre style="background:#f6f6f6;padding:8px;border-radius:4px;white-space:pre-wrap;">{{ json_encode($webhookInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
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
    </fieldset>
    @endif

    <div style="margin-top:12px;">
        <form action="{{ route('alfred.admin.personas.destroy', $persona) }}" method="post" onsubmit="return confirm('Remover persona?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger">Remover</button>
        </form>
    </div>
</div>
@endsection
