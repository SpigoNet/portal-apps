@extends('Alfred::layouts.app')

@section('title', 'Nova Persona - Administração')

@section('content')
<div class="card">
    <h2>Nova Persona</h2>
    <a href="{{ route('alfred.admin.personas.index') }}" class="btn btn-secondary">← Voltar</a>

    <form action="{{ route('alfred.admin.personas.store') }}" method="post" enctype="multipart/form-data" style="margin-top:16px; max-width:800px;">
        @csrf

        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Canal de envio</label>
            <select name="canal" class="form-control" required>
                <option value="whatsapp" {{ old('canal', 'whatsapp') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                <option value="telegram" {{ old('canal') == 'telegram' ? 'selected' : '' }}>Telegram</option>
            </select>
        </div>

        <div class="form-group">
            <label>WhatsApp Group JID</label>
            <input type="text" name="whatsapp_group_jid" value="{{ old('whatsapp_group_jid') }}" class="form-control">
        </div>

        <fieldset style="border:1px solid #eee; padding:12px 16px; border-radius:6px; margin-bottom:16px;">
            <legend style="font-weight:600; padding:0 6px;">Configurações do Bot Telegram</legend>

            <div class="form-group">
                <label>Token do Bot</label>
                <input type="text" name="telegram_token" value="{{ old('telegram_token') }}" class="form-control">
            </div>

            <div class="form-group">
                <label>Chat ID</label>
                <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id') }}" class="form-control">
                <small style="color:var(--text-muted);">ID do chat, grupo ou canal onde a mensagem será enviada.</small>
            </div>
        </fieldset>

        <div class="form-group">
            <label>Personality (JSON)</label>
            <textarea name="personality" class="form-control" rows="6">{{ old('personality') }}</textarea>
        </div>

        <div class="form-group">
            <label>Aparência do personagem</label>
            <textarea name="aparencia" class="form-control" rows="6" placeholder="Descrição visual bem detalhada: idade aparente, corpo, rosto, cabelo, olhos, roupas, acessórios, paleta de cores e estilo de arte (ex.: anime, aquarela, 3D)...">{{ old('aparencia') }}</textarea>
            <small style="color:var(--text-muted);">Base fixa usada para gerar a imagem do personagem nos agendamentos do Pidgey (opção "Enviar imagem do personagem"). A expressão e o sentimento mudam a cada mensagem; a aparência não.</small>
        </div>

        <div class="form-group">
            <label>Imagem de referência (opcional)</label>
            <input type="file" name="imagem_referencia" accept="image/*" class="form-control">
            <small style="color:var(--text-muted);">Se o modelo de imagem aceitar referência (image-to-image), ela é usada para manter o personagem consistente.</small>
        </div>

        <div class="form-group">
            <label>Metadata (JSON)</label>
            <textarea name="metadata" class="form-control" rows="4">{{ old('metadata') }}</textarea>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}> Ativo</label>
        </div>

        <div style="display:flex; gap:8px;">
            <button class="btn btn-primary">Salvar</button>
        </div>
    </form>
</div>
@endsection
