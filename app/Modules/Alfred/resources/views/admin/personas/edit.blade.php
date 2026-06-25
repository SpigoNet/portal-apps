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
            <label>WhatsApp Group JID</label>
            <input type="text" name="whatsapp_group_jid" value="{{ old('whatsapp_group_jid', $persona->whatsapp_group_jid) }}" class="form-control">
        </div>

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

    <div style="margin-top:12px;">
        <form action="{{ route('alfred.admin.personas.destroy', $persona) }}" method="post" onsubmit="return confirm('Remover persona?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger">Remover</button>
        </form>
    </div>
</div>
@endsection
