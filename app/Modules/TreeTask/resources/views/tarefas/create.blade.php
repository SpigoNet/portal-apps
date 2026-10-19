<x-TreeTask::layout titulo="Nova tarefa" ativo="projetos">
    <nav class="tt-crumb" aria-label="Trilha">
        <a href="{{ route('treetask.index') }}">Projetos</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <a href="{{ route('treetask.lista', $fase->id_projeto) }}">{{ $fase->projeto->nome }}</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <b>{{ $fase->nome }}</b>
    </nav>

    <header>
        <h1 class="tt-h1">Nova tarefa</h1>
        <p class="tt-muted" style="margin:6px 0 0">Na fase “{{ $fase->nome }}”.</p>
    </header>

    <form class="tt-card pad" style="max-width:760px;display:flex;flex-direction:column;gap:18px" action="{{ route('treetask.tarefas.store') }}" method="POST">
        @csrf
        <input type="hidden" name="id_fase" value="{{ $fase->id_fase }}">

        <div class="tt-field">
            <label class="tt-label" for="titulo">Título *</label>
            <input class="tt-input" id="titulo" type="text" name="titulo" value="{{ old('titulo') }}" required>
            @error('titulo') <span class="tt-erro">{{ $message }}</span> @enderror
        </div>

        <div class="tt-field">
            <label class="tt-label" for="descricao">Descrição</label>
            <textarea class="tt-textarea" id="descricao" name="descricao" rows="4">{{ old('descricao') }}</textarea>
        </div>

        <div class="tt-form-grid">
            <div class="tt-field">
                <label class="tt-label" for="id_user_responsavel">Responsável *</label>
                <select class="tt-select" id="id_user_responsavel" name="id_user_responsavel" required>
                    <option value="">Selecione...</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) old('id_user_responsavel', auth()->id()) === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('id_user_responsavel') <span class="tt-erro">{{ $message }}</span> @enderror
            </div>

            <div class="tt-field">
                <label class="tt-label" for="prioridade">Prioridade</label>
                <select class="tt-select" id="prioridade" name="prioridade">
                    @foreach(['Baixa', 'Média', 'Alta', 'Urgente'] as $prio)
                        <option value="{{ $prio }}" @selected(old('prioridade', 'Média') === $prio)>{{ $prio }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-label" for="data_vencimento">Vencimento</label>
                <input class="tt-input" id="data_vencimento" type="date" name="data_vencimento" value="{{ old('data_vencimento') }}">
            </div>

            <div class="tt-field">
                <label class="tt-label" for="estimativa_tempo">Estimativa (horas)</label>
                <input class="tt-input" id="estimativa_tempo" type="number" step="0.5" name="estimativa_tempo" value="{{ old('estimativa_tempo') }}">
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center">
            <a class="tt-btn ghost" href="{{ route('treetask.lista', $fase->id_projeto) }}">Cancelar</a>
            <button type="submit" class="tt-btn pri">Criar tarefa</button>
        </div>
    </form>
</x-TreeTask::layout>
