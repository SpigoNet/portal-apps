@extends('Alfred::layouts.app')

@section('title', 'Editar Rotina - Alfred')

@section('content')
<div class="card">
    <h2 class="mb-2">✏️ Editar Rotina</h2>
    
    <form action="{{ route('alfred.rotinas.update', $rotina) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="titulo" class="required">Título da Rotina</label>
            <input type="text" name="titulo" id="titulo" class="form-control" required 
                   value="{{ $rotina->titulo }}">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição (opcional)</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="2">{{ $rotina->descricao }}</textarea>
        </div>

        <div class="form-group">
            <label for="categoria" class="required">Categoria</label>
            <select name="categoria" id="categoria" class="form-control" required>
                <option value="saude" {{ $rotina->categoria == 'saude' ? 'selected' : '' }}>💪 Saúde</option>
                <option value="trabalho" {{ $rotina->categoria == 'trabalho' ? 'selected' : '' }}>💼 Trabalho</option>
                <option value="lazer" {{ $rotina->categoria == 'lazer' ? 'selected' : '' }}>🎨 Lazer</option>
                <option value="financeiro" {{ $rotina->categoria == 'financeiro' ? 'selected' : '' }}>💰 Financeiro</option>
                <option value="familia" {{ $rotina->categoria == 'familia' ? 'selected' : '' }}>👨‍👩‍👧‍👦 Família</option>
                <option value="estudo" {{ $rotina->categoria == 'estudo' ? 'selected' : '' }}>📚 Estudo</option>
                <option value="outro" {{ $rotina->categoria == 'outro' ? 'selected' : '' }}>📌 Outro</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo_recorrencia" class="required">Tipo de Recorrência</label>
            <select name="tipo_recorrencia" id="tipo_recorrencia" class="form-control" required onchange="mostrarConfigRecorrencia()">
                <option value="diaria" {{ $rotina->tipo_recorrencia == 'diaria' ? 'selected' : '' }}>📅 Diária</option>
                <option value="semanal" {{ $rotina->tipo_recorrencia == 'semanal' ? 'selected' : '' }}>📆 Semanal</option>
                <option value="mensal" {{ $rotina->tipo_recorrencia == 'mensal' ? 'selected' : '' }}>📅 Mensal</option>
                <option value="unica" {{ $rotina->tipo_recorrencia == 'unica' ? 'selected' : '' }}>📌 Única</option>
            </select>
        </div>

        @php
            $diasSelecionados = $rotina->config_recorrencia['dias_semana'] ?? [];
        @endphp
        <div id="config-semanal" class="form-group" style="background: var(--bg-tertiary); padding: 16px; border-radius: 12px; {{ $rotina->tipo_recorrencia == 'semanal' ? '' : 'display: none;' }}">
            <label class="required">Dias da Semana</label>
            <div class="checkbox-group">
                <label><input type="checkbox" name="dias_semana[]" value="1" {{ in_array(1, $diasSelecionados) ? 'checked' : '' }}> <span>Seg</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="2" {{ in_array(2, $diasSelecionados) ? 'checked' : '' }}> <span>Ter</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="3" {{ in_array(3, $diasSelecionados) ? 'checked' : '' }}> <span>Qua</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="4" {{ in_array(4, $diasSelecionados) ? 'checked' : '' }}> <span>Qui</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="5" {{ in_array(5, $diasSelecionados) ? 'checked' : '' }}> <span>Sex</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="6" {{ in_array(6, $diasSelecionados) ? 'checked' : '' }}> <span>Sáb</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="0" {{ in_array(0, $diasSelecionados) ? 'checked' : '' }}> <span>Dom</span></label>
            </div>
        </div>

        <div id="config-mensal" class="form-group" style="background: var(--bg-tertiary); padding: 16px; border-radius: 12px; {{ $rotina->tipo_recorrencia == 'mensal' ? '' : 'display: none;' }}">
            <label for="dia_mes" class="required">Dia do Mês</label>
            <input type="number" name="dia_mes" id="dia_mes" class="form-control" min="1" max="31" value="{{ $rotina->config_recorrencia['dia_mes'] ?? 1 }}">
        </div>

        <div id="config-unica" class="form-group" style="background: var(--bg-tertiary); padding: 16px; border-radius: 12px; {{ $rotina->tipo_recorrencia == 'unica' ? '' : 'display: none;' }}">
            <label for="data_unica" class="required">Data</label>
            <input type="date" name="data_unica" id="data_unica" class="form-control" value="{{ $rotina->config_recorrencia['data'] ?? '' }}">
        </div>

        <div class="form-group">
            <label for="horario_sugerido">Horário Sugerido</label>
            <input type="time" name="horario_sugerido" id="horario_sugerido" class="form-control" value="{{ $rotina->horario_sugerido ? substr($rotina->horario_sugerido, 0, 5) : '' }}">
        </div>

        <div class="form-group">
            <label for="prioridade">Prioridade</label>
            <select name="prioridade" id="prioridade" class="form-control">
                <option value="3" {{ $rotina->prioridade == 3 ? 'selected' : '' }}>🔴 Alta</option>
                <option value="2" {{ $rotina->prioridade == 2 ? 'selected' : '' }}>🟡 Média</option>
                <option value="1" {{ $rotina->prioridade == 1 ? 'selected' : '' }}>🔵 Baixa</option>
            </select>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                <input type="checkbox" name="ativa" value="1" {{ $rotina->ativa ? 'checked' : '' }} style="width: 24px; height: 24px;">
                <span style="font-weight: 600;">Rotina ativa</span>
            </label>
        </div>

        <div class="btn-group-vertical">
            <button type="submit" class="btn btn-success">Atualizar Rotina</button>
            <a href="{{ route('alfred.admin.rotinas') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    function mostrarConfigRecorrencia() {
        const tipo = document.getElementById('tipo_recorrencia').value;
        document.getElementById('config-semanal').style.display = tipo === 'semanal' ? 'block' : 'none';
        document.getElementById('config-mensal').style.display = tipo === 'mensal' ? 'block' : 'none';
        document.getElementById('config-unica').style.display = tipo === 'unica' ? 'block' : 'none';
    }
</script>
@endsection
