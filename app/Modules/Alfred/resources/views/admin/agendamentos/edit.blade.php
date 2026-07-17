@extends('Alfred::layouts.app')

@section('title', 'Editar Agendamento')

@section('content')
<div class="card">
    <h2 class="mb-2">Editar Agendamento</h2>
    <a href="{{ route('alfred.admin.agendamentos.index') }}" class="btn btn-secondary btn-sm mb-2">← Voltar</a>

    <form action="{{ route('alfred.admin.agendamentos.update', $agendamento) }}" method="post">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="persona_id">Persona</label>
            <select name="persona_id" id="persona_id" class="form-control" required>
                <option value="">Selecione...</option>
                @foreach($personas as $p)
                    <option value="{{ $p->id }}" {{ old('persona_id', $agendamento->persona_id) == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} ({{ $p->slug }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="mensagem">Instrução para gerar a mensagem</label>
            <textarea name="mensagem" id="mensagem" class="form-control" rows="4" required>{{ old('mensagem', $agendamento->mensagem) }}</textarea>
            <small style="color:var(--text-muted);">A IA usa esta instrução e o perfil da persona para montar o texto final enviado.</small>
        </div>

        <div class="form-group">
            <label for="intervalo_minutos">Intervalo (minutos)</label>
            <input type="number" name="intervalo_minutos" id="intervalo_minutos" class="form-control" value="{{ old('intervalo_minutos', $agendamento->intervalo_minutos) }}" min="10" max="1440" required>
        </div>

        <div style="display:flex; gap:16px;">
            <div class="form-group" style="flex:1;">
                <label for="hora_inicio">Horário início</label>
                <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" value="{{ old('hora_inicio', $agendamento->hora_inicio) }}" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label for="hora_fim">Horário fim</label>
                <input type="time" name="hora_fim" id="hora_fim" class="form-control" value="{{ old('hora_fim', $agendamento->hora_fim) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Dias da semana</label>
            @php
                $diasNomes = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
                $diasSelecionados = old('dias_semana', $agendamento->dias_semana ?? []);
            @endphp
            <div class="checkbox-group">
                @foreach($diasNomes as $num => $nome)
                    <label>
                        <input type="checkbox" name="dias_semana[]" value="{{ $num }}" {{ in_array($num, $diasSelecionados) ? 'checked' : '' }}>
                        {{ $nome }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="ativa" value="1" {{ old('ativa', $agendamento->ativa) ? 'checked' : '' }}>
                Ativa
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Salvar</button>
    </form>
</div>
@endsection
