<x-yomi::layout title="{{ $manga->titulo }}" :active="null">
    <div class="detail-head">
        <div class="cover detail-cover">
            @if($cover)
                <img src="{{ $cover }}" alt="Capa de {{ $manga->titulo }}">
            @endif
            <span class="cover-copy">{{ Str::limit($manga->titulo, 15) }}</span>
        </div>
        <div>
            <div class="eyebrow">CATÁLOGO · {{ $manga->tipo ?? 'MANGÁ' }}</div>
            <h1>{{ $manga->titulo }}</h1>
            <div style="color:#f3aaa3">{{ $manga->titulo_original ?? 'Título original não informado' }} · {{ $manga->status_publicacao }}</div>
            <p>{{ $manga->sinopse ?? 'Ainda não há uma sinopse cadastrada para esta obra.' }}</p>
            <div class="period-tabs">
                <span class="pill active">★ {{ $manga->nota_media ?? '—' }}</span>
                <span class="pill">{{ $manga->capitulos_conhecidos ?? $chapters->count() }} capítulos</span>
                <span class="pill">{{ $manga->volumes_conhecidos ?? '?' }} volumes</span>
            </div>
            @if($progress !== null)
                <form method="POST" action="{{ route('yomi.mangas.remove', $manga) }}" onsubmit="return confirm('Remover esta obra da sua estante?')">
                    @csrf
                    <button class="btn btn-secondary btn-small" type="submit" style="margin-top:14px">✕ Remover da estante</button>
                </form>
            @endif
        </div>
    </div>

    <div class="detail-layout">
        <section>
            <div class="panel" style="margin-bottom:18px">
                <div class="section-row" style="margin:0 0 12px">
                    <div>
                        <div class="section-kicker">ONDE VOCÊ PAROU</div>
                        <h2 class="section-title">
                            Cap. {{ $progress?->ultimo_capitulo_lido ?? 0 }}
                            <small style="color:var(--muted);font-size:13px">de {{ $manga->capitulos_conhecidos ?? $chapters->count() }}</small>
                        </h2>
                    </div>
                    <span class="pill active">{{ $progress?->status?->label() ?? 'Pretendo ler' }}</span>
                </div>
                <div class="progress-meta">
                    <span>Progresso da obra</span>
                    <strong>{{ $manga->capitulos_conhecidos ? round(($progress?->ultimo_capitulo_lido ?? 0) / $manga->capitulos_conhecidos * 100) : 0 }}%</strong>
                </div>
                <div class="progress-line">
                    <span style="width:{{ $manga->capitulos_conhecidos ? min(100, (($progress?->ultimo_capitulo_lido ?? 0) / $manga->capitulos_conhecidos) * 100) : 0 }}%"></span>
                </div>
                <br>
                <form method="POST" action="{{ route('yomi.mangas.mark-next', $manga) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">✓ Marcar próximo capítulo como lido</button>
                </form>
            </div>

            <div x-data="{
                search: '',
                filter: 'todos',
                sortAsc: {{ $chapterOrder === 'asc' ? 'true' : 'false' }},
                syncing: false,
                matches(number, title, isRead) {
                    if (this.filter === 'lidos' && !isRead) return false;
                    if (this.filter === 'nao_lidos' && isRead) return false;
                    if (!this.search) return true;
                    const q = this.search.toLowerCase();
                    return String(number).toLowerCase().includes(q) || String(title).toLowerCase().includes(q);
                }
            }">
                <div class="section-row" style="align-items:center;flex-wrap:wrap;gap:10px">
                    <div>
                        <h2 class="section-title" style="margin:0">Capítulos ({{ $chapters->count() }})</h2>
                        <small style="color:var(--muted)">{{ $chapterReadCount }} de {{ $chapterTotal }} capítulos lidos ({{ $chapterProgressPercent }}%)</small>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <form method="POST" action="{{ route('yomi.mangas.sync-chapters', $manga) }}" @submit="syncing = true">
                            @csrf
                            <button class="btn btn-secondary btn-small" type="submit" :disabled="syncing" title="Atualiza a lista de capítulos a partir dos provedores externos">
                                <span x-text="syncing ? 'Atualizando capítulos…' : '🔄 Atualizar capítulos da API'"></span>
                            </button>
                        </form>
                    </div>
                </div>

                @if($chapters->isNotEmpty())
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0 16px;flex-wrap:wrap">
                        <div style="display:flex;gap:6px">
                            <button type="button" @click="filter = 'todos'" :class="filter === 'todos' ? 'pill active' : 'pill'" style="cursor:pointer;border:0;line-height:1">
                                Todos ({{ $chapters->count() }})
                            </button>
                            <button type="button" @click="filter = 'lidos'" :class="filter === 'lidos' ? 'pill active' : 'pill'" style="cursor:pointer;border:0;line-height:1">
                                Lidos ({{ $chapters->filter(fn ($c) => $readIds->has($c->id))->count() }})
                            </button>
                            <button type="button" @click="filter = 'nao_lidos'" :class="filter === 'nao_lidos' ? 'pill active' : 'pill'" style="cursor:pointer;border:0;line-height:1">
                                Não lidos ({{ $chapters->filter(fn ($c) => ! $readIds->has($c->id))->count() }})
                            </button>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Filtrar capítulo..."
                                style="background:var(--surface-2);border:1px solid var(--line);border-radius:6px;padding:6px 12px;color:var(--paper);font-size:12px;outline:none;width:160px"
                            >
                            <button
                                type="button"
                                @click="window.location.search = 'ordem=' + (sortAsc ? 'desc' : 'asc')"
                                class="pill"
                                style="cursor:pointer;border:0;padding:6px 10px;font-size:11px"
                                x-text="sortAsc ? '▲ Mais antigos' : '▼ Mais recentes'"
                            ></button>
                        </div>
                    </div>
                @endif

                <div class="chapter-list" style="display:flex;flex-direction:column;gap:7px">
                    @forelse($chapters as $chapter)
                        @php
                            $isRead = $readIds->has($chapter->id);
                        @endphp
                        <div
                            class="chapter"
                            x-show="matches('{{ $chapter->numero }}', '{{ addslashes($chapter->titulo ?? '') }}', {{ $isRead ? 'true' : 'false' }})"
                        >
                            <span class="chapter-number">{{ $chapter->numero }}</span>
                            <div style="flex:1">
                                <strong>{{ $chapter->titulo ?? 'Capítulo '.$chapter->numero }}</strong>
                                <small>{{ $chapter->data_publicacao?->format('d/m/Y') ?? 'Data não informada' }}</small>
                            </div>

                            <div style="display:flex;align-items:center;gap:12px;margin-left:auto">
                                @if($isRead)
                                    <span class="chapter-status" style="color:var(--green);font-weight:700">✓ LIDO</span>
                                    <form method="POST" action="{{ route('yomi.mangas.chapters.toggle', [$manga, $chapter]) }}" style="margin:0">
                                        @csrf
                                        <button class="btn btn-secondary btn-small" type="submit" style="padding:4px 8px;font-size:10px" title="Desmarcar capítulo como lido">
                                            Desmarcar
                                        </button>
                                    </form>
                                @else
                                    <span class="chapter-status" style="color:var(--muted)">NÃO LIDO</span>
                                    <form method="POST" action="{{ route('yomi.mangas.chapters.toggle', [$manga, $chapter]) }}" style="margin:0">
                                        @csrf
                                        <button class="btn btn-primary btn-small" type="submit" style="padding:4px 8px;font-size:10px" title="Marcar capítulo como lido">
                                            ✓ Marcar lido
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:24px;text-align:center;background:var(--surface);border:1px dashed var(--line);border-radius:6px">
                            <p style="margin-bottom:12px;color:var(--muted)">Nenhum capítulo cadastrado para esta obra ainda.</p>
                            <form method="POST" action="{{ route('yomi.mangas.sync-chapters', $manga) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">
                                    🔄 Sincronizar capítulos da API agora
                                </button>
                            </form>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside>
            <div class="panel">
                <div class="section-kicker">FICHA TÉCNICA</div>
                <div class="genre-row"><div class="genre-head"><span>Status</span><strong>{{ $manga->status_publicacao }}</strong></div></div>
                <div class="genre-row"><div class="genre-head"><span>Classificação</span><strong>{{ $manga->classificacao_etaria ?? 'Livre' }}</strong></div></div>
                <div class="genre-row"><div class="genre-head"><span>Fonte</span><strong>{{ $manga->fonte_original ?? 'Jikan' }}</strong></div></div>
                <div class="genre-row"><div class="genre-head"><span>Gêneros</span><strong>{{ $manga->generos->pluck('nome')->join(', ') ?: 'Ainda não definidos' }}</strong></div></div>
            </div>
        </aside>
    </div>
</x-yomi::layout>
