@php
    use App\Modules\TreeTask\Models\Tarefa;
    use App\Modules\TreeTask\Support\Aparencia;

    $temFiltro = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '' && $v !== '0' && $v !== [])->isNotEmpty();
    $umProjeto = count($filtros['projeto']) === 1 ? $filtros['projeto'][0] : null;
    $novaTarefa = $umProjeto ? route('treetask.kanban.nova_tarefa', ['projeto' => $umProjeto]) : null;
    $nomesProjetos = $projetos->pluck('nome', 'id_projeto');
@endphp
<x-TreeTask::layout titulo="Kanban" ativo="kanban" :fab="$novaTarefa">
    <header class="tt-ph">
        <div>
            <div class="tt-eyebrow">
                @if($umProjeto) {{ $nomesProjetos[$umProjeto] ?? 'Projeto' }} · tarefas por status @else Todas as tarefas, por status @endif
            </div>
            <h1 class="tt-h1">Kanban</h1>
        </div>
        <div class="tt-acoes">
            @if($umProjeto)
                <a class="tt-btn" href="{{ route('treetask.lista', $umProjeto) }}">Lista do projeto</a>
                <a class="tt-btn" href="{{ route('treetask.tree.view', $umProjeto) }}">Ver árvore</a>
            @endif
            @if($novaTarefa)
                <a class="tt-btn pri" href="{{ $novaTarefa }}">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Nova tarefa
                </a>
            @else
                <span class="tt-muted" style="font-size:13px">Filtre por um único projeto para criar tarefas</span>
            @endif
        </div>
    </header>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('treetask.kanban') }}" id="filtros" class="tt-filtros">
        <label class="tt-busca" style="width:240px;max-width:100%">
            <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input class="tt-input busca" type="search" name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Título ou descrição" aria-label="Buscar tarefa">
        </label>

        {{-- Projetos: um, vários ou todos (nenhum marcado). Aplica ao fechar a lista. --}}
        <div style="position:relative"
             x-data="{ aberto: false, mudou: false, marcados: {{ count($filtros['projeto']) }} }"
             @click.outside="if (aberto) { aberto = false; if (mudou) $root.closest('form').submit(); }">
            <button type="button" class="tt-flt" @click="aberto = !aberto" :aria-expanded="aberto">
                Projetos: <b x-text="marcados === 0 ? 'Todos' : (marcados === 1 ? '1 projeto' : marcados + ' projetos')"></b>
                <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-show="aberto" x-cloak class="tt-card" style="position:absolute;z-index:30;top:50px;left:0;width:280px;max-height:320px;overflow-y:auto;padding:10px;box-shadow:0 20px 50px rgba(35,27,44,.18)">
                @foreach($projetos as $p)
                    <label style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;cursor:pointer;min-height:44px">
                        <input type="checkbox" class="tt-ck" name="projeto[]" value="{{ $p->id_projeto }}"
                               @checked(in_array($p->id_projeto, $filtros['projeto'], true))
                               @change="mudou = true; marcados += $event.target.checked ? 1 : -1">
                        <span style="width:10px;height:10px;border-radius:50%;background: {{ Aparencia::corProjeto($p->id_projeto) }};flex:none"></span>
                        {{ $p->nome }}
                    </label>
                @endforeach
                <button type="submit" class="tt-btn pri bloco" style="margin-top:6px">Aplicar</button>
            </div>
        </div>

        <label class="tt-flt">Responsável:
            <select name="responsavel" onchange="this.form.submit()" aria-label="Responsável">
                <option value="">Todos</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" @selected(($filtros['responsavel'] ?? null) == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="tt-flt">Prioridade:
            <select name="prioridade" onchange="this.form.submit()" aria-label="Prioridade">
                <option value="">Todas</option>
                @foreach(Tarefa::PRIORIDADES as $prioridade)
                    <option value="{{ $prioridade }}" @selected(($filtros['prioridade'] ?? null) === $prioridade)>{{ $prioridade }}</option>
                @endforeach
            </select>
        </label>

        <label class="tt-flt {{ ! empty($filtros['atrasadas']) ? 'on' : '' }}" style="cursor:pointer">
            <input type="checkbox" class="tt-ck" name="atrasadas" value="1" onchange="this.form.submit()" @checked(! empty($filtros['atrasadas']))>
            Só atrasadas
        </label>

        <label class="tt-flt {{ ! empty($filtros['todas_concluidas']) ? 'on' : '' }}" style="cursor:pointer" title="Por padrão só as concluídas nos últimos {{ $diasConcluidas }} dias">
            <input type="checkbox" class="tt-ck" name="todas_concluidas" value="1" onchange="this.form.submit()" @checked(! empty($filtros['todas_concluidas']))>
            Todas as concluídas
        </label>

        <button type="submit" class="tt-btn">Filtrar</button>
        @if($temFiltro)
            <a href="{{ route('treetask.kanban') }}" class="tt-btn ghost">Limpar</a>
        @endif
    </form>

    {{-- Colunas por status --}}
    <div class="tt-kanban" aria-label="Colunas por status">
        @foreach($colunas as $status)
            @php $tarefas = $porStatus->get($status, collect()); @endphp
            <section class="tt-col" aria-label="{{ $status }}">
                <div class="tt-col-h">
                    <span class="tt-chip {{ Aparencia::statusClasse($status) }}">{{ $status }}</span>
                    <span class="tt-mono tt-muted" style="font-size:12px" data-contador>{{ $tarefas->count() }}</span>
                </div>

                <div class="tt-col-lista coluna" data-status="{{ $status }}">
                    @foreach($tarefas as $tarefa)
                        @php
                            $concluida = $tarefa->status === Tarefa::CONCLUIDA;
                            $atrasada = ! $concluida && $tarefa->data_vencimento && $tarefa->data_vencimento->isPast() && ! $tarefa->data_vencimento->isToday();
                            $projetoDaTarefa = $tarefa->fase->projeto;
                        @endphp
                        <div class="tt-k {{ $concluida ? 'feita' : '' }}" data-tarefa-id="{{ $tarefa->id_tarefa }}">
                            <div class="tt-k-topo">
                                <a class="tt-k-proj" style="color: {{ Aparencia::corProjeto($projetoDaTarefa->id_projeto) }}" href="{{ route('treetask.kanban', ['projeto' => [$projetoDaTarefa->id_projeto]]) }}">{{ $projetoDaTarefa->nome }}</a>
                                <span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}">{{ $tarefa->prioridade }}</span>
                            </div>

                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                                <a class="tt-k-tit" href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}">{{ $tarefa->titulo }}</a>
                                <a class="tt-icbtn" style="width:32px;height:32px;margin:-4px -6px 0 0" href="{{ route('treetask.tarefas.edit', $tarefa->id_tarefa) }}" title="Editar" aria-label="Editar {{ $tarefa->titulo }}">
                                    <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                            </div>

                            <div class="tt-muted" style="font-size:12.5px">{{ $tarefa->fase->nome }}</div>

                            <div class="tt-k-base">
                                <span style="display:inline-flex;gap:12px;align-items:center">
                                    @if($tarefa->data_vencimento && ! $concluida)
                                        <span class="tt-meta {{ $atrasada ? 'atraso' : '' }}">
                                            <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>{{ $tarefa->data_vencimento->format('d/m') }}
                                        </span>
                                    @endif
                                </span>
                                @if($tarefa->responsavel)
                                    <span class="tt-av" style="background: {{ Aparencia::corAvatar($tarefa->responsavel->id) }}" title="{{ $tarefa->responsavel->name }}">{{ Aparencia::iniciais($tarefa->responsavel->name) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($novaTarefa)
                    <a class="tt-add" href="{{ $novaTarefa }}">
                        <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Adicionar tarefa
                    </a>
                @endif
            </section>
        @endforeach
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const urlMover = '{{ route('treetask.kanban.mover', ['id' => '__ID__']) }}';

            const atualizarContadores = () => document.querySelectorAll('.coluna').forEach(coluna => {
                coluna.parentElement.querySelector('[data-contador]').textContent = coluna.querySelectorAll('[data-tarefa-id]').length;
            });

            document.querySelectorAll('.coluna').forEach(coluna => {
                new Sortable(coluna, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'tt-fantasma',
                    onEnd: function (evt) {
                        const destino = evt.to;
                        const ids = Array.from(destino.querySelectorAll('[data-tarefa-id]')).map(el => el.dataset.tarefaId);
                        atualizarContadores();

                        fetch(urlMover.replace('__ID__', evt.item.dataset.tarefaId), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({status: destino.dataset.status, ids: ids})
                        }).then(response => {
                            if (!response.ok) throw new Error();
                        }).catch(() => {
                            alert('Não foi possível mover a tarefa. A página será recarregada.');
                            window.location.reload();
                        });
                    }
                });
            });
        });
    </script>
</x-TreeTask::layout>
