@extends('Alfred::layouts.app')

@section('title', 'Personas - Administração')

@section('content')
<div class="card">
    <h2 class="mb-2">Personas</h2>
    <a href="{{ route('alfred.admin.index') }}" class="btn btn-secondary">← Voltar</a>

    <div style="margin-top: 16px; display:flex; gap:12px; align-items:center; justify-content:space-between;">
        <form action="{{ route('alfred.admin.personas.store') }}" method="post" style="display:flex; gap:8px; align-items:center;">
            @csrf
            <input type="text" name="name" placeholder="Nome (ex: Chopper)" required class="form-control" style="width:220px;">
            <input type="text" name="slug" placeholder="slug (ex: chopper)" required class="form-control" style="width:160px;">
            <input type="text" name="whatsapp_group_jid" placeholder="120363427048537280 ou 120363427048537280@g.us" class="form-control" style="width:320px;">
            <button class="btn btn-primary">Criar</button>
        </form>

        <div style="text-align:right; color:var(--text-muted);">Dica: informe o grupo como número ou com sufixo <code>@g.us</code></div>
    </div>

    <div style="margin-top:20px; overflow:auto;">
    <table class="table" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; color:var(--text-muted); font-size:0.9rem;">
                <th style="padding:10px 8px; border-bottom:1px solid #eee; width:60px;">ID</th>
                <th style="padding:10px 8px; border-bottom:1px solid #eee;">Nome</th>
                <th style="padding:10px 8px; border-bottom:1px solid #eee; width:160px;">Slug</th>
                <th style="padding:10px 8px; border-bottom:1px solid #eee; width:260px;">Grupo WhatsApp</th>
                <th style="padding:10px 8px; border-bottom:1px solid #eee; width:90px;">Ativo</th>
                <th style="padding:10px 8px; border-bottom:1px solid #eee; width:260px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($personas as $p)
            <tr style="border-bottom:1px solid #f4f4f4;">
                <td style="padding:10px 8px; vertical-align:middle;">{{ $p->id }}</td>
                <td style="padding:10px 8px; vertical-align:middle; font-weight:600;">{{ $p->name }}</td>
                <td style="padding:10px 8px; vertical-align:middle; color:var(--text-muted);">{{ $p->slug }}</td>
                <td style="padding:10px 8px; vertical-align:middle; font-family:monospace; color:#333;">
                    @if($p->whatsapp_group_jid)
                        {{ Str::limit($p->whatsapp_group_jid, 30) }}
                    @else
                        —
                    @endif
                </td>
                <td style="padding:10px 8px; vertical-align:middle;">{{ $p->active ? 'Sim' : 'Não' }}</td>
                <td style="padding:10px 8px; vertical-align:middle;">
                    <a href="{{ route('alfred.admin.personas.edit', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    <a href="{{ route('alfred.admin.personas.show', $p) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                    <form action="{{ route('alfred.admin.personas.send-test', $p) }}" method="post" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-outline-info">Enviar teste</button>
                    </form>
                    <form action="{{ route('alfred.admin.personas.destroy', $p) }}" method="post" style="display:inline;" onsubmit="return confirm('Remover persona {{ $p->name }}?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Remover</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
