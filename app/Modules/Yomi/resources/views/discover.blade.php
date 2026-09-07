<x-yomi::layout title="Descobrir" :active="null">
    <div class="page-head">
        <div>
            <div class="eyebrow">● EXPLORAR · DESCOBERTA</div>
            <h1>Descobrir</h1>
            <p>Busque novos mangás nos provedores externos e adicione direto à sua estante.</p>
        </div>
    </div>

    <form class="library-toolbar" method="GET" action="{{ route('yomi.discover') }}">
        <input class="filter-input" type="search" name="q" value="{{ $query }}" placeholder="Buscar mangás por título, autor ou gênero..." autofocus>
        <button class="btn btn-primary" type="submit">⌕ Buscar</button>
        @if($query !== '')<a class="btn btn-secondary" href="{{ route('yomi.discover') }}">Limpar</a>@endif
    </form>

    @if($error)<div class="flash" style="background:var(--red);color:#230c0e">{{ $error }}</div>@endif

    <div class="section-row">
        <h2 class="section-title">@if($query !== '' && $results !== []) Resultados para “{{ $query }}” @elseif($query !== '') Nenhum título encontrado @else Populares agora @endif</h2>
        @if($results !== [])<span class="eyebrow">{{ count($results) }} títulos · direto das APIs</span>@endif
    </div>

    @if($results === [])
        <div class="empty-state">
            <div class="eyebrow">@if($query !== '') SEM RESULTADOS @elseif($error) SERVIÇO INDISPONÍVEL @else PRONTO PARA EXPLORAR @endif</div>
            <h2>@if($query !== '') Nada encontrado para essa busca. @elseif($error) Não foi possível contatar os provedores agora. @else Busque algo ou dê uma olhada nos destaques. @endif</h2>
            <p>@if($error) Tente novamente em instantes — seus dados já cadastrados permanecem intactos. @else Use a busca acima ou navegue pelos populares. @endif</p>
        </div>
    @else
        <div class="manga-grid">
            @foreach($results as $external)
                @php
                    $key = null;

                    foreach ($external->externalIds as $id) {
                        if (isset($id['provider'], $id['external_id'])) {
                            $key = $id['provider'].':'.$id['external_id'];

                            break;
                        }
                    }

                    $provider = $key !== null ? explode(':', $key)[0] : null;
                    $externalId = $key !== null ? explode(':', $key, 2)[1] : null;
                    $isExisting = $key !== null && isset($existing[$key]);
                    $detailUrl = $isExisting
                        ? route('yomi.mangas.show', $existing[$key])
                        : ($key !== null ? route('yomi.discover.external', ['provider' => $provider, 'externalId' => $externalId]) : null);
                @endphp
                <article class="manga-card">
                    @if($detailUrl !== null)<a href="{{ $detailUrl }}">@endif<div class="cover manga-poster">@if($external->imageUrl)<img src="{{ $external->imageUrl }}" alt="Capa de {{ $external->title }}" loading="lazy">@endif<span class="cover-copy">{{ Str::limit($external->title, 14) }}</span></div>@if($detailUrl !== null)</a>@endif
                    <div class="card-body">
                        <h3>@if($detailUrl !== null)<a href="{{ $detailUrl }}">@endif{{ Str::limit($external->title, 32) }}@if($detailUrl !== null)</a>@endif</h3>
                        <p>{{ Str::upper($external->type ?? 'mangá') }} · {{ collect($external->genres)->take(2)->implode(', ') ?: '—' }}</p>
                        <div class="card-foot">
                            @if($external->score)<strong>★ {{ number_format($external->score, 2, ',', '.') }}</strong>@else<strong>{{ $external->chapters ? $external->chapters.' cap.' : '—' }}</strong>@endif
                            @if($isExisting)
                                <a class="btn btn-secondary btn-small" href="{{ route('yomi.mangas.show', $existing[$key]) }}">Na estante</a>
                            @elseif($key !== null)
                                <form method="POST" action="{{ route('yomi.discover.register') }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">@csrf<input type="hidden" name="provider" value="{{ $provider }}"><input type="hidden" name="external_id" value="{{ $externalId }}"><select name="status" style="background:var(--surface);border:1px solid var(--line);border-radius:6px;color:var(--paper);padding:6px 8px;font:inherit;font-size:11px;max-width:110px">@foreach(\App\Modules\Yomi\Enums\StatusLeitura::cases() as $status)<option value="{{ $status->value }}" @selected($status === \App\Modules\Yomi\Enums\StatusLeitura::PretendoLer)>{{ $status->label() }}</option>@endforeach</select><button class="btn btn-primary btn-small" type="submit">+ Adicionar</button></form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-yomi::layout>