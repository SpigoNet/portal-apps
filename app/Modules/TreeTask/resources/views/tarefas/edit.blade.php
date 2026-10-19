@php
    use App\Modules\TreeTask\Models\Tarefa;

    $voltar = request('origin') === 'focus' ? route('treetask.focus.index') : route('treetask.tarefas.show', $tarefa->id_tarefa);
@endphp
<x-TreeTask::layout titulo="Editar tarefa" ativo="projetos" :quill="true">
    <nav class="tt-crumb" aria-label="Trilha">
        <a href="{{ route('treetask.index') }}">Projetos</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <a href="{{ route('treetask.lista', $tarefa->fase->id_projeto) }}">{{ $tarefa->fase->projeto->nome }}</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <a href="{{ $voltar }}">{{ \Illuminate\Support\Str::limit($tarefa->titulo, 40) }}</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <b>Editar</b>
    </nav>

    <header>
        <h1 class="tt-h1">Editar tarefa</h1>
    </header>

    <form id="edit-task-form" class="tt-card pad" style="max-width:820px;display:flex;flex-direction:column;gap:18px" action="{{ route('treetask.tarefas.update', $tarefa->id_tarefa) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="origin" value="{{ request('origin') }}">

        <div class="tt-form-grid" style="padding:16px;border-radius:14px;background:#FBF9FD;border:1px solid var(--tt-line)">
            <div class="tt-field">
                <label class="tt-label" for="id_fase">Fase</label>
                <select class="tt-select" id="id_fase" name="id_fase">
                    @foreach($fases as $fase)
                        <option value="{{ $fase->id_fase }}" @selected($tarefa->id_fase == $fase->id_fase)>{{ $fase->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-label" for="status">Status</label>
                <select class="tt-select" id="status" name="status" style="font-weight:700">
                    @foreach(['A Fazer', 'Planejamento', 'Em Andamento', 'Aguardando resposta', Tarefa::CONCLUIDA] as $status)
                        <option value="{{ $status }}" @selected($tarefa->status == $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="tt-field">
            <label class="tt-label" for="titulo">Título *</label>
            <input class="tt-input" id="titulo" type="text" name="titulo" value="{{ old('titulo', $tarefa->titulo) }}" required>
            @error('titulo') <span class="tt-erro">{{ $message }}</span> @enderror
        </div>

        <div class="tt-field">
            <span class="tt-label">Descrição</span>
            <div class="tt-editor">
                <div id="editor-container" style="height: 220px; background:#fff">
                    {!! old('descricao', $tarefa->descricao) !!}
                </div>
            </div>
            <input type="hidden" name="descricao" id="descricao-hidden">
        </div>

        <div class="tt-form-grid">
            <div class="tt-field">
                <label class="tt-label" for="id_user_responsavel">Responsável *</label>
                <select class="tt-select" id="id_user_responsavel" name="id_user_responsavel" required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($tarefa->id_user_responsavel == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-label" for="prioridade">Prioridade</label>
                <select class="tt-select" id="prioridade" name="prioridade">
                    @foreach(Tarefa::PRIORIDADES as $prio)
                        <option value="{{ $prio }}" @selected($tarefa->prioridade == $prio)>{{ $prio }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-label" for="data_vencimento">Vencimento</label>
                <input class="tt-input" id="data_vencimento" type="date" name="data_vencimento"
                       value="{{ $tarefa->data_vencimento ? \Carbon\Carbon::parse($tarefa->data_vencimento)->format('Y-m-d') : '' }}">
            </div>

            <div class="tt-field">
                <label class="tt-label" for="estimativa_tempo">Estimativa (h)</label>
                <input class="tt-input" id="estimativa_tempo" type="number" step="0.5" name="estimativa_tempo" value="{{ old('estimativa_tempo', $tarefa->estimativa_tempo) }}">
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--tt-line-2)">
            <a class="tt-btn" href="{{ $voltar }}">Cancelar</a>
            <button type="submit" class="tt-btn pri">Salvar alterações</button>
        </div>
    </form>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Descreva os detalhes da tarefa...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'header': [1, 2, 3, false] }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['clean']
                    ]
                }
            });

            // Antes do envio, copia o HTML do editor para o campo escondido
            document.getElementById('edit-task-form').onsubmit = function () {
                var description = document.querySelector('input[name=descricao]');
                description.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
            };
        });
    </script>
</x-TreeTask::layout>
