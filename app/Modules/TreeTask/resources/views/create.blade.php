<x-TreeTask::layout titulo="Novo projeto" ativo="projetos">
    <nav class="tt-crumb" aria-label="Trilha">
        <a href="{{ route('treetask.index') }}">Projetos</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <b>Novo projeto</b>
    </nav>

    <header>
        <h1 class="tt-h1">Novo projeto</h1>
        <p class="tt-muted" style="margin:6px 0 0">Dê um nome, descreva o objetivo e, se quiser, marque as datas.</p>
    </header>

    <form class="tt-card pad" style="max-width:720px;display:flex;flex-direction:column;gap:18px" action="{{ route('treetask.store') }}" method="POST">
        @csrf

        <div class="tt-field">
            <label class="tt-label" for="nome">Nome do projeto</label>
            <input class="tt-input" type="text" name="nome" id="nome" value="{{ old('nome') }}" required>
            @error('nome') <span class="tt-erro">{{ $message }}</span> @enderror
        </div>

        <div class="tt-field">
            <label class="tt-label" for="descricao">Descrição</label>
            <textarea class="tt-textarea" name="descricao" id="descricao" rows="4" required>{{ old('descricao') }}</textarea>
            @error('descricao') <span class="tt-erro">{{ $message }}</span> @enderror
        </div>

        <div class="tt-form-grid">
            <div class="tt-field">
                <label class="tt-label" for="data_inicio">Início</label>
                <input class="tt-input" type="date" name="data_inicio" id="data_inicio" value="{{ old('data_inicio') }}">
                @error('data_inicio') <span class="tt-erro">{{ $message }}</span> @enderror
            </div>
            <div class="tt-field">
                <label class="tt-label" for="data_prevista_termino">Previsão de término</label>
                <input class="tt-input" type="date" name="data_prevista_termino" id="data_prevista_termino" value="{{ old('data_prevista_termino') }}">
                @error('data_prevista_termino') <span class="tt-erro">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center">
            <a class="tt-btn ghost" href="{{ route('treetask.index') }}">Cancelar</a>
            <button class="tt-btn pri" type="submit">Salvar projeto</button>
        </div>
    </form>
</x-TreeTask::layout>
