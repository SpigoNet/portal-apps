@extends('Alfred::layouts.app')

@section('title', 'Nova Rotina - Alfred')

@section('content')
<div class="card">
    <h2 class="mb-2">➕ Nova Rotina</h2>
    
    <form action="{{ route('alfred.rotinas.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="titulo" class="required">Título da Rotina</label>
            <input type="text" name="titulo" id="titulo" class="form-control" required 
                   placeholder="Ex: Fazer alongamento">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição (opcional)</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="2"
                      placeholder="Detalhes sobre a rotina..."></textarea>
        </div>

        <div class="form-group">
            <label for="categoria" class="required">Categoria</label>
            <select name="categoria" id="categoria" class="form-control" required>
                <option value="saude">💪 Saúde</option>
                <option value="trabalho">💼 Trabalho</option>
                <option value="lazer">🎨 Lazer</option>
                <option value="financeiro">💰 Financeiro</option>
                <option value="familia">👨‍👩‍👧‍👦 Família</option>
                <option value="estudo">📚 Estudo</option>
                <option value="outro">📌 Outro</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo_recorrencia" class="required">Tipo de Recorrência</label>
            <select name="tipo_recorrencia" id="tipo_recorrencia" class="form-control" required onchange="mostrarConfigRecorrencia()">
                <option value="diaria">📅 Diária</option>
                <option value="semanal">📆 Semanal</option>
                <option value="mensal">📅 Mensal</option>
                <option value="unica">📌 Única</option>
            </select>
        </div>

        <div id="config-semanal" class="form-group" style="display: none; background: var(--bg-tertiary); padding: 16px; border-radius: 12px;">
            <label class="required">Dias da Semana</label>
            <div class="checkbox-group">
                <label><input type="checkbox" name="dias_semana[]" value="1"> <span>Seg</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="2"> <span>Ter</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="3"> <span>Qua</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="4"> <span>Qui</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="5"> <span>Sex</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="6"> <span>Sáb</span></label>
                <label><input type="checkbox" name="dias_semana[]" value="0"> <span>Dom</span></label>
            </div>
        </div>

        <div id="config-mensal" class="form-group" style="display: none; background: var(--bg-tertiary); padding: 16px; border-radius: 12px;">
            <label for="dia_mes" class="required">Dia do Mês</label>
            <input type="number" name="dia_mes" id="dia_mes" class="form-control" min="1" max="31" value="1">
        </div>

        <div id="config-unica" class="form-group" style="display: none; background: var(--bg-tertiary); padding: 16px; border-radius: 12px;">
            <label for="data_unica" class="required">Data</label>
            <input type="date" name="data_unica" id="data_unica" class="form-control">
        </div>

        <div class="form-group">
            <label for="horario_sugerido">Horário Sugerido</label>
            <input type="time" name="horario_sugerido" id="horario_sugerido" class="form-control">
        </div>

        <div class="form-group">
            <label for="prioridade">Prioridade</label>
            <select name="prioridade" id="prioridade" class="form-control">
                <option value="3">🔴 Alta</option>
                <option value="2" selected>🟡 Média</option>
                <option value="1">🔵 Baixa</option>
            </select>
        </div>

        <div class="btn-group-vertical">
            <button type="submit" class="btn btn-success">Criar Rotina</button>
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
