<x-yomi::layout title="{{ $external->title }}" :active="null">
    <a class="btn btn-secondary btn-small" href="{{ route('yomi.discover') }}" style="margin-bottom:18px">← Voltar para Descobrir</a>

    <div class="detail-head">
        <div class="cover detail-cover">@if($external->imageUrl)<img src="{{ $external->imageUrl }}" alt="Capa de {{ $external->title }}">@endif<span class="cover-copy">{{ Str::limit($external->title, 15) }}</span></div>
        <div>
            <div class="eyebrow">DESCOBERTA · {{ \App\Modules\Yomi\Enums\ProviderName::tryFrom($provider)?->label() }} </div>
            <h1>{{ $external->title }}</h1>
            <div style="color:#f3aaa3">{{ $external->originalTitle ?? 'Título original não informado' }} · {{ \App\Modules\Yomi\Enums\StatusPublicacao::tryFrom($external->status)?->label() ?? $external->status }}</div>
            <p>{{ $external->synopsis ?? 'Ainda não há uma sinopse disponível para esta obra no provedor.' }}</p>
            <div class="period-tabs">
                <span class="pill active">★ {{ $external->score !== null ? number_format($external->score, 2, ',', '.') : '—' }}</span>
                <span class="pill">{{ $external->chapters ?? (count($chapters) > 0 ? count($chapters) : '?') }} capítulos</span>
                <span class="pill">{{ $external->volumes ?? '?' }} volumes</span>
            </div>
            <div style="margin-top:18px">
                <form method="POST" action="{{ route('yomi.discover.register') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    @csrf
                    <input type="hidden" name="provider" value="{{ $provider }}">
                    <input type="hidden" name="external_id" value="{{ $externalId }}">
                    <select name="status" style="background:var(--surface);border:1px solid var(--line);border-radius:6px;color:var(--paper);padding:10px 12px;font:inherit;font-size:12px">
                        @foreach(\App\Modules\Yomi\Enums\StatusLeitura::cases() as $status)
                            <option value="{{ $status->value }}" @selected($status === \App\Modules\Yomi\Enums\StatusLeitura::PretendoLer)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">+ Adicionar à estante</button>
                </form>
            </div>
        </div>
    </div>

    <div class="detail-layout">
        <section>
            <div class="panel">
                <div class="section-kicker">SINOPSE</div>
                <p>{{ $external->synopsis ?? 'Sem sinopse disponível.' }}</p>
            </div>

            <div class="panel" style="margin-top:16px" x-data="{
                search: '',
                sortAsc: {{ ($ordem ?? 'asc') === 'asc' ? 'true' : 'false' }},
                matches(number, title) {
                    if (!this.search) return true;
                    const q = this.search.toLowerCase();
                    return String(number).toLowerCase().includes(q) || String(title).toLowerCase().includes(q);
                }
            }">
                <div class="section-row" style="align-items:center;flex-wrap:wrap;gap:10px">
                    <div>
                        <h2 class="section-title" style="margin:0">Capítulos ({{ count($chapters) }})</h2>
                        <small style="color:var(--muted)">Lista de capítulos disponíveis para visualização prévia</small>
                    </div>
                </div>

                @if(!empty($chapters))
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0 16px;flex-wrap:wrap">
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
                        <div
                            class="chapter"
                            x-show="matches('{{ $chapter->number }}', '{{ addslashes($chapter->title ?? '') }}')"
                        >
                            <span class="chapter-number">{{ $chapter->number ?? '—' }}</span>
                            <div style="flex:1">
                                <strong>{{ $chapter->title ?? 'Capítulo '.$chapter->number }}</strong>
                                <small>{{ $chapter->publishedAt ? \Carbon\Carbon::parse($chapter->publishedAt)->format('d/m/Y') : 'Data não informada' }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:24px;text-align:center;background:var(--surface);border:1px dashed var(--line);border-radius:6px">
                            <p style="margin:0;color:var(--muted)">Nenhum capítulo disponível para esta obra no momento.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
        <aside>
            <div class="panel">
                <div class="section-kicker">FICHA TÉCNICA</div>
                <div class="genre-row"><div class="genre-head"><span>Status</span><strong>{{ \App\Modules\Yomi\Enums\StatusPublicacao::tryFrom($external->status)?->label() ?? $external->status }}</strong></div></div>
                @if($external->type)<div class="genre-row"><div class="genre-head"><span>Tipo</span><strong>{{ $external->type }}</strong></div></div>@endif
                @if($external->ageRating)<div class="genre-row"><div class="genre-head"><span>Classificação</span><strong>{{ $external->ageRating }}</strong></div></div>@endif
                @if($external->publishedFrom)<div class="genre-row"><div class="genre-head"><span>Publicação</span><strong>{{ $external->publishedFrom }}@if($external->publishedTo) → {{ $external->publishedTo }}@endif</strong></div></div>@endif
                @if($external->genres !== [])<div class="genre-row"><div class="genre-head"><span>Gêneros</span><strong>{{ implode(', ', array_slice($external->genres, 0, 5)) }}</strong></div></div>@endif
                @if($external->creators !== [])<div class="genre-row"><div class="genre-head"><span>Autores</span><strong>{{ collect($external->creators)->take(3)->pluck('name')->join(', ') }}</strong></div></div>@endif
            </div>
        </aside>
    </div>
</x-yomi::layout>