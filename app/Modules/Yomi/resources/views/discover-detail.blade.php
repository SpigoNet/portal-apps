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
                <span class="pill">{{ $external->chapters ?? '?' }} capítulos</span>
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