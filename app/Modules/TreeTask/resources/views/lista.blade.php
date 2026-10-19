@php
    use App\Modules\TreeTask\Models\Tarefa;
    use App\Modules\TreeTask\Support\Aparencia;
@endphp
<x-TreeTask::layout :titulo="$projeto->nome" ativo="projetos" :fab="route('treetask.kanban.nova_tarefa', ['projeto' => $projeto->id_projeto])">
    @include('TreeTask::partials.projeto-cabecalho', ['aba' => 'lista'])

    <div class="tt-lista">
        <div class="tt-lista-fases" id="fases" x-data="{ fechadas: {} }">
            @forelse($projeto->fases as $fase)
                @php
                    $feitas = $fase->tarefas->where('status', Tarefa::CONCLUIDA)->count();
                    $totalFase = $fase->tarefas->count();
                @endphp
                <section class="tt-card" style="overflow:hidden" data-fase-id="{{ $fase->id_fase }}">
                    <div class="tt-fase-h">
                        <svg class="tt-i s tt-grip fase-handle" viewBox="0 0 24 24" aria-hidden="true" title="Arraste para reordenar a fase"><circle cx="9" cy="6" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                        <button type="button" class="tt-icbtn" style="width:32px;height:32px" @click="fechadas[{{ $fase->id_fase }}] = ! fechadas[{{ $fase->id_fase }}]" :aria-expanded="! fechadas[{{ $fase->id_fase }}]" aria-label="Recolher ou abrir {{ $fase->nome }}">
                            <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true" :style="fechadas[{{ $fase->id_fase }}] ? 'transform:rotate(-90deg)' : ''"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <h2>{{ $fase->nome }}</h2>
                        <span style="display:inline-flex;align-items:center;gap:10px">
                            <span class="tt-bar tt-hide-m" style="width:120px"><i style="width: {{ $totalFase > 0 ? round($feitas / $totalFase * 100) : 0 }}%"></i></span>
                            <span class="tt-mono tt-muted" style="font-size:12.5px">{{ $feitas }}/{{ $totalFase }}</span>
                        </span>
                        <a class="tt-icbtn" style="width:36px;height:36px" href="{{ route('treetask.ai.index', ['type' => 'phase', 'id' => $fase->id_fase]) }}" title="Assistente IA nesta fase" aria-label="Assistente IA em {{ $fase->nome }}">
                            <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>
                        </a>
                        <a class="tt-icbtn" style="width:36px;height:36px" href="{{ route('treetask.tarefas.create', $fase->id_fase) }}" title="Adicionar tarefa" aria-label="Adicionar tarefa em {{ $fase->nome }}">
                            <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        </a>
                    </div>

                    <div class="lista-tarefas" x-show="! fechadas[{{ $fase->id_fase }}]">
                        @forelse($fase->tarefas as $tarefa)
                            @php
                                $concluida = $tarefa->status === Tarefa::CONCLUIDA;
                                $atrasada = ! $concluida && $tarefa->data_vencimento && $tarefa->data_vencimento->isPast() && ! $tarefa->data_vencimento->isToday();
                            @endphp
                            <div class="tt-row {{ $concluida ? 'feita' : '' }}" data-tarefa-id="{{ $tarefa->id_tarefa }}">
                                <svg class="tt-i s tt-grip tarefa-handle" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="6" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="18" r="1"/></svg>
                                <form action="{{ route('treetask.tarefas.updateStatus', $tarefa->id_tarefa) }}" method="POST" style="display:flex">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $concluida ? 'A Fazer' : Tarefa::CONCLUIDA }}">
                                    <input type="checkbox" class="tt-ck" @checked($concluida) onchange="this.form.submit()" aria-label="{{ $concluida ? 'Reabrir' : 'Concluir' }} {{ $tarefa->titulo }}">
                                </form>
                                <a class="tt-row-tit" href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}">{{ $tarefa->titulo }}</a>
                                <span class="tt-prio {{ Aparencia::prioridadeClasse($tarefa->prioridade) }}">{{ $tarefa->prioridade }}</span>
                                <span class="tt-chip {{ Aparencia::statusClasse($tarefa->status) }}">{{ $tarefa->status }}</span>
                                <span class="tt-meta tt-venc {{ $atrasada ? 'atraso' : '' }}" style="min-width:56px">{{ $tarefa->data_vencimento && ! $concluida ? $tarefa->data_vencimento->format('d/m') : '' }}</span>
                                @if($tarefa->responsavel)
                                    <span class="tt-av" style="background: {{ Aparencia::corAvatar($tarefa->responsavel->id) }}" title="{{ $tarefa->responsavel->name }}">{{ Aparencia::iniciais($tarefa->responsavel->name) }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="tt-row tt-muted">Nenhuma tarefa nesta fase.</div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="tt-vazio">Este projeto ainda não tem fases. Crie a primeira abaixo.</div>
            @endforelse

            <form class="tt-card tt-novafase" action="{{ route('treetask.fases.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id_projeto" value="{{ $projeto->id_projeto }}">
                <input class="tt-input" style="flex:1 1 240px;width:auto" type="text" name="nome" placeholder="Nome da nova fase" aria-label="Nome da nova fase" required>
                <button type="submit" class="tt-btn">
                    <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Nova fase
                </button>
            </form>
        </div>

        <aside class="tt-lista-lado">
            <div class="tt-card pad">
                <div class="tt-stat-rot">Progresso geral</div>
                <div class="tt-stat-val" style="font-size:40px">{{ $resumo['pct'] }}%</div>
                <div class="tt-bar" style="height:8px;margin:10px 0"><i style="width: {{ $resumo['pct'] }}%"></i></div>
                <div class="tt-muted" style="font-size:13px">{{ $resumo['feitas'] }} de {{ $resumo['total'] }} tarefas concluídas</div>
            </div>

            <div class="tt-card pad" style="display:flex;flex-direction:column;gap:14px">
                <div style="font-weight:700">Próximos vencimentos</div>
                @forelse($proximos as $p)
                    <a href="{{ route('treetask.tarefas.show', $p->id_tarefa) }}" class="tt-venc" style="text-decoration:none">
                        <span class="tt-data-cx"><small>{{ $p->data_vencimento->locale('pt_BR')->translatedFormat('M') }}</small><b>{{ $p->data_vencimento->format('d') }}</b></span>
                        <span style="font-weight:600;line-height:1.3">{{ $p->titulo }}</span>
                    </a>
                @empty
                    <span class="tt-muted">Nada com data marcada.</span>
                @endforelse
            </div>

            @if($resumo['equipe']->isNotEmpty())
                <div class="tt-card pad" style="display:flex;flex-direction:column;gap:12px">
                    <div style="font-weight:700">Equipe</div>
                    @foreach($resumo['equipe'] as $u)
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="tt-av" style="background: {{ Aparencia::corAvatar($u->id) }}">{{ Aparencia::iniciais($u->name) }}</span>
                            <span style="font-weight:500">{{ $u->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const enviar = (url, ids) => fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ids: ids})
            }).then(r => { if (!r.ok) throw new Error(); })
              .catch(() => { alert('Não foi possível salvar a nova ordem. A página será recarregada.'); window.location.reload(); });

            new Sortable(document.getElementById('fases'), {
                animation: 150,
                handle: '.fase-handle',
                draggable: '[data-fase-id]',
                onEnd: function (evt) {
                    enviar('{{ route('treetask.reorder.fases') }}', Array.from(evt.to.querySelectorAll('[data-fase-id]')).map(el => el.dataset.faseId));
                }
            });

            document.querySelectorAll('.lista-tarefas').forEach(lista => {
                new Sortable(lista, {
                    animation: 150,
                    handle: '.tarefa-handle',
                    draggable: '[data-tarefa-id]',
                    onEnd: function (evt) {
                        enviar('{{ route('treetask.reorder.tarefas') }}', Array.from(evt.to.querySelectorAll('[data-tarefa-id]')).map(el => el.dataset.tarefaId));
                    }
                });
            });
        });
    </script>
</x-TreeTask::layout>
