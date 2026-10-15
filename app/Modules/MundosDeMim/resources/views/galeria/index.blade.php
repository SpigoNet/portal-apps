<x-MundosDeMim::layout>
    <div class="mm-wrap mm-stack" style="gap:32px">
        <div style="display:flex;flex-direction:column;gap:10px">
            <h1 class="mm-h1">Seu atlas</h1>
            <p class="mm-lead">
                @if($generations->total() > 0)
                    {{ $generations->total() }} {{ $generations->total() === 1 ? 'postal guardado' : 'postais guardados' }}, do mais novo ao mais antigo.
                @else
                    Os postais que chegarem ficam guardados aqui.
                @endif
            </p>
        </div>

        @if($generations->isEmpty())
            <div class="mm-card" style="display:flex;flex-direction:column;gap:12px;align-items:flex-start">
                <h2 class="mm-display" style="margin:0;font-size:28px;font-weight:700">Nenhum postal por aqui ainda</h2>
                <p style="margin:0;color:var(--mm-texto-suave);font-size:16px;line-height:1.5">Complete seu retrato, ligue alguns mundos e espere a próxima viagem.</p>
                <a href="{{ route('mundos-de-mim.perfil.index') }}" class="mm-btn mm-btn-primario">Fazer meu retrato</a>
            </div>
        @else
            <div class="mm-grade-postais">
                @foreach($generations as $art)
                    <figure style="margin:0;display:flex;flex-direction:column;gap:10px">
                        <a href="{{ $art->image_url }}" target="_blank" rel="noopener" class="mm-postal" aria-label="Abrir o postal de {{ $art->reference_date->format('d/m/Y') }} em tamanho grande">
                            <img src="{{ $art->image_url }}" alt="{{ $art->theme?->name ?? 'Mundo surpresa' }}, {{ $art->reference_date->format('d/m/Y') }}" loading="lazy">
                        </a>
                        <figcaption style="display:flex;flex-direction:column;gap:2px;font-size:14px">
                            <span style="display:flex;justify-content:space-between;gap:8px">
                                <b>{{ $art->theme?->name ?? 'Mundo surpresa' }}</b>
                                <span style="color:var(--mm-texto-suave);white-space:nowrap">{{ $art->reference_date->format('d/m') }}</span>
                            </span>
                            <span style="display:flex;justify-content:space-between;gap:8px;color:var(--mm-texto-suave)">
                                <span>{{ $art->theme?->is_seasonal ? 'Edição de temporada' : 'Sempre aberto' }}</span>
                                <a href="{{ $art->image_url }}" target="_blank" rel="noopener" download style="color:var(--mm-bruma);font-weight:600;padding:4px 0">Baixar HD</a>
                            </span>
                            @if($art->story)
                                <details style="margin-top:6px">
                                    <summary style="cursor:pointer;padding:8px 0;color:var(--mm-bruma);font-weight:600">Ler a história</summary>
                                    <p style="margin:0;padding-bottom:6px;line-height:1.55;color:#D6D3EE;white-space:pre-line">{{ $art->story }}</p>
                                </details>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            <div>{{ $generations->links() }}</div>
        @endif
    </div>
</x-MundosDeMim::layout>
