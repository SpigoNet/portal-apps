@extends('Alfred::layouts.app')

@section('title', 'Novo Agendamento')

@section('content')
<div class="card">
    <h2 class="mb-2">Novo Agendamento</h2>
    <a href="{{ route('alfred.admin.agendamentos.index') }}" class="btn btn-secondary btn-sm mb-2">← Voltar</a>

    <form action="{{ route('alfred.admin.agendamentos.store') }}" method="post">
        @csrf

        <div class="form-group">
            <label for="persona_id">Persona</label>
            <select name="persona_id" id="persona_id" class="form-control" required>
                <option value="">Selecione...</option>
                @foreach($personas as $p)
                    <option value="{{ $p->id }}" {{ old('persona_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} ({{ $p->slug }})
                    </option>
                @endforeach
            </select>
            @error('persona_id')
                <small style="color:var(--accent-red);">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="mensagem">Mensagem</label>
            <textarea name="mensagem" id="mensagem" class="form-control" rows="4" required placeholder="Ex: Hora de beber água! Cuidado com o corpo, tá?">{{ old('mensagem') }}</textarea>
            @error('mensagem')
                <small style="color:var(--accent-red);">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="intervalo_minutos">Intervalo (minutos)</label>
            <input type="number" name="intervalo_minutos" id="intervalo_minutos" class="form-control" value="{{ old('intervalo_minutos', 120) }}" min="10" max="1440" required>
            <small style="color:var(--text-muted);">Ex: 120 = a cada 2 horas</small>
            @error('intervalo_minutos')
                <small style="color:var(--accent-red);">{{ $message }}</small>
            @enderror
        </div>

        <div style="display:flex; gap:16px;">
            <div class="form-group" style="flex:1;">
                <label for="hora_inicio">Horário início</label>
                <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" value="{{ old('hora_inicio', '08:00') }}" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label for="hora_fim">Horário fim</label>
                <input type="time" name="hora_fim" id="hora_fim" class="form-control" value="{{ old('hora_fim', '22:00') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Dias da semana</label>
            @php
                $diasNomes = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
                $diasSelecionados = old('dias_semana', [1,2,3,4,5,6,7]);
            @endphp
            <div class="checkbox-group">
                @foreach($diasNomes as $num => $nome)
                    <label>
                        <input type="checkbox" name="dias_semana[]" value="{{ $num }}" {{ in_array($num, $diasSelecionados) ? 'checked' : '' }}>
                        {{ $nome }}
                    </label>
                @endforeach
            </div>
            @error('dias_semana')
                <small style="color:var(--accent-red);">{{ $message }}</small>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">Criar Agendamento</button>
    </form>
</div>
@endsection
