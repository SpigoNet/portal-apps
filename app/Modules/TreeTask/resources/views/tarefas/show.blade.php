@php
    use App\Modules\TreeTask\Models\Tarefa;
    use App\Modules\TreeTask\Support\Aparencia;

    $concluida = $tarefa->status === Tarefa::CONCLUIDA;
    $atrasada = ! $concluida && $tarefa->data_vencimento && $tarefa->data_vencimento->isPast() && ! $tarefa->data_vencimento->isToday();
    $projeto = $tarefa->fase->projeto;
    $descricao = (string) $tarefa->descricao;
@endphp
<x-TreeTask::layout :titulo="$tarefa->titulo" ativo="projetos">
    <nav class="tt-crumb" aria-label="Trilha">
        <a href="{{ route('treetask.index') }}">Projetos</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <a href="{{ route('treetask.lista', $projeto->id_projeto) }}">{{ $projeto->nome }}</a>
        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        <b>{{ $tarefa->fase->nome }}</b>
    </nav>

    <div class="tt-tarefa">
        <div class="tt-tarefa-corpo">
            <header style="display:flex;flex-direction:column;gap:14px">
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
                    <span class="tt-chip g {{ Aparencia::statusClasse($tarefa->status) }}">{{ $tarefa->status }}</span>
                    <span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}" style="font-weight:600">Prioridade {{ mb_strtolower((string) $tarefa->prioridade) }}</span>
                </div>
                <h1 class="tt-h1" style="font-size:32px">{{ $tarefa->titulo }}</h1>
            </header>

            <section class="tt-card pad" aria-label="Descrição">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h2 class="tt-sec-tit">Descrição</h2>
                    <a class="tt-btn ghost sm" href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}">Editar</a>
                </div>
                @if(trim(strip_tags($descricao)) !== '')
                    <div class="tt-prosa">
                        @if(function_exists('clean')) {!! clean($descricao) !!} @else {!! nl2br(e($descricao)) !!} @endif
                    </div>
                @else
                    <p class="tt-muted" style="margin:0">Sem descrição.</p>
                @endif
            </section>

            <section class="tt-card pad" aria-label="Anexos">
                <h2 class="tt-sec-tit">Anexos · {{ $tarefa->anexos->count() }}</h2>

                @foreach($tarefa->anexos as $anexo)
                    <div class="tt-arquivo">
                        <div class="tt-arquivo-ic">
                            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>
                        </div>
                        <div style="flex:1 1 200px;min-width:0">
                            <div style="font-weight:600;overflow-wrap:anywhere">{{ $anexo->nome_arquivo }}</div>
                            <div class="tt-muted" style="font-size:13px">{{ number_format($anexo->tamanho / 1024, 1, ',', '.') }} KB</div>
                        </div>
                        <a class="tt-btn ghost sm" href="{{ route('treetask.anexos.download', $anexo->id_anexo) }}" style="color:var(--tt-brand-d)">Baixar</a>
                        <form action="{{ route('treetask.anexos.destroy', ['taskId' => $tarefa->id_tarefa, 'anexoId' => $anexo->id_anexo]) }}" method="POST" onsubmit="return confirm('Remover este anexo?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="tt-btn perigo sm">Remover</button>
                        </form>
                    </div>
                @endforeach

                <form class="tt-upload" action="{{ route('treetask.anexos.store', $tarefa->id_tarefa) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <svg class="tt-i l" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--tt-brand)"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>
                    <input type="file" name="arquivo" required aria-label="Escolher arquivo">
                    <button type="submit" class="tt-btn pri">Enviar</button>
                </form>
                @error('arquivo') <span class="tt-erro" style="display:block;margin-top:8px">{{ $message }}</span> @enderror
            </section>
        </div>

        <aside class="tt-tarefa-lado">
            <div style="display:flex;flex-direction:column;gap:10px">
                @if(! $concluida)
                    <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Em Andamento">
                        <button type="submit" class="tt-btn pri bloco">
                            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>Entrar em foco
                        </button>
                    </form>
                    <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Concluído">
                        <button type="submit" class="tt-btn bloco">
                            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>Marcar como concluída
                        </button>
                    </form>
                @endif
                <a class="tt-btn bloco" href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}">Editar tarefa</a>
            </div>

            <dl class="tt-card tt-kv">
                <div><dt>Status</dt><dd><span class="tt-chip {{ Aparencia::statusClasse($tarefa->status) }}">{{ $tarefa->status }}</span></dd></div>
                <div><dt>Prioridade</dt><dd><span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}" style="color:var(--tt-ink);font-size:14px;font-weight:600">{{ $tarefa->prioridade }}</span></dd></div>
                <div>
                    <dt>Responsável</dt>
                    <dd>
                        @if($tarefa->responsavel)
                            <span class="tt-av" style="background: {{ Aparencia::corAvatar($tarefa->responsavel->id) }}">{{ Aparencia::iniciais($tarefa->responsavel->name) }}</span>{{ \Illuminate\Support\Str::words($tarefa->responsavel->name, 1, '') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div><dt>Vencimento</dt><dd @if($atrasada) style="color:#A4231A" @endif>{{ $tarefa->data_vencimento ? $tarefa->data_vencimento->format('d/m/Y') : '—' }}</dd></div>
                @if($tarefa->estimativa_tempo)
                    <div><dt>Estimativa</dt><dd>{{ rtrim(rtrim(number_format((float) $tarefa->estimativa_tempo, 1, ',', ''), '0'), ',') }} h</dd></div>
                @endif
                <div><dt>Fase</dt><dd>{{ $tarefa->fase->nome }}</dd></div>
                <div><dt>Projeto</dt><dd>{{ $projeto->nome }}</dd></div>
            </dl>

            <a class="tt-btn ghost" href="{{ route('treetask.ai.index', ['type' => 'task', 'id' => $tarefa->id_tarefa]) }}" style="align-self:flex-start">
                <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>Assistente IA nesta tarefa
            </a>
        </aside>
    </div>
</x-TreeTask::layout>
