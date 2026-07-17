@extends('Alfred::layouts.app')

@section('title', 'Agendamentos - Administração')

@section('content')
<div class="card">
    <div class="flex-between mb-2">
        <h2>Agendamentos</h2>
        <a href="{{ route('alfred.admin.agendamentos.create') }}" class="btn btn-primary btn-sm">+ Novo</a>
    </div>
    <a href="{{ route('alfred.admin.index') }}" class="btn btn-secondary btn-sm mb-2">← Voltar</a>

    @if($agendamentos->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">⏰</div>
            <p>Nenhum agendamento criado.</p>
        </div>
    @else
        <div style="overflow:auto;">
        <table class="table" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; color:var(--text-muted); font-size:0.9rem;">
                    <th style="padding:10px 8px; border-bottom:1px solid #eee;">Persona</th>
                    <th style="padding:10px 8px; border-bottom:1px solid #eee;">Instrução</th>
                    <th style="padding:10px 8px; border-bottom:1px solid #eee; width:100px;">Intervalo</th>
                    <th style="padding:10px 8px; border-bottom:1px solid #eee; width:120px;">Horário</th>
                    <th style="padding:10px 8px; border-bottom:1px solid #eee; width:80px;">Ativo</th>
                    <th style="padding:10px 8px; border-bottom:1px solid #eee; width:280px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agendamentos as $a)
                <tr style="border-bottom:1px solid #f4f4f4;">
                    <td style="padding:10px 8px; vertical-align:middle; font-weight:600;">{{ $a->persona->name ?? '—' }}</td>
                    <td style="padding:10px 8px; vertical-align:middle; color:var(--text-secondary);">{{ Str::limit($a->mensagem, 60) }}</td>
                    <td style="padding:10px 8px; vertical-align:middle;">{{ $a->intervalo_minutos }}min</td>
                    <td style="padding:10px 8px; vertical-align:middle; font-family:monospace;">{{ $a->hora_inicio }}–{{ $a->hora_fim }}</td>
                    <td style="padding:10px 8px; vertical-align:middle;">
                        @if($a->ativa)
                            <span class="badge" style="background:var(--accent-green); color:#fff;">Ativo</span>
                        @else
                            <span class="badge" style="background:var(--text-muted); color:#fff;">Inativo</span>
                        @endif
                    </td>
                    <td style="padding:10px 8px; vertical-align:middle;">
                        <a href="{{ route('alfred.admin.agendamentos.edit', $a) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        <form action="{{ route('alfred.admin.agendamentos.toggle', $a) }}" method="post" style="display:inline;">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">{{ $a->ativa ? 'Desativar' : 'Ativar' }}</button>
                        </form>
                        <form action="{{ route('alfred.admin.agendamentos.send-test', $a) }}" method="post" style="display:inline;">
                            @csrf
                            <button class="btn btn-sm btn-outline-info">Testar</button>
                        </form>
                        <form action="{{ route('alfred.admin.agendamentos.destroy', $a) }}" method="post" style="display:inline;" onsubmit="return confirm('Remover agendamento?');">
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
    @endif
</div>
@endsection
