@php
    $primeiroNome = \Illuminate\Support\Str::before(auth()->user()->name ?? 'viajante', ' ');
    $hora = (int) now()->format('G');
    $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
    $planoRotulo = ($stats['plano'] ?? null) === 'prime' ? 'Expedição' : 'Passagem';
@endphp
<x-MundosDeMim::layout>
    <div class="mm-wrap mm-stack">

        <div style="display:flex;flex-direction:column;gap:8px">
            <h1 class="mm-h1">{{ $saudacao }}, {{ $primeiroNome }}.</h1>
            <p class="mm-lead">
                @if($ultima)
                    Seu postal de hoje está no atlas. Você já guardou {{ $stats['total_artes'] }} {{ $stats['total_artes'] === 1 ? 'postal' : 'postais' }}.
                @else
                    Seu atlas ainda está vazio. Conte como você é e o primeiro postal chega na próxima viagem.
                @endif
            </p>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:stretch">

            <div class="mm-card" style="flex:2 1 520px;min-width:0;padding:20px;display:flex;flex-wrap:wrap;gap:24px">
                <div class="mm-postal {{ $ultima ? '' : 'mm-cena-1' }}" style="flex:1 1 260px;min-width:0">
                    @if($ultima)
                        <img src="{{ $ultima->image_url }}" alt="Postal de {{ $ultima->reference_date->format('d/m/Y') }}">
                    @endif
                </div>
                <div style="flex:1 1 260px;min-width:0;display:flex;flex-direction:column;justify-content:center;gap:16px;padding:12px 8px">
                    @if($ultima)
                        <span class="mm-chip" style="align-self:flex-start;color:var(--mm-menta)">{{ $ultima->reference_date->isToday() ? 'Postal de hoje' : 'Último postal · '.$ultima->reference_date->format('d/m') }}</span>
                        <h2 class="mm-display" style="margin:0;font-size:40px;line-height:1.02;font-weight:800">{{ $ultima->theme?->name ?? 'Mundo surpresa' }}</h2>
                        <p style="margin:0;font-size:16px;line-height:1.55;color:var(--mm-texto-suave)">
                            {{ $ultima->story ?: ($ultima->theme?->is_seasonal ? 'Um mundo de temporada, só por alguns dias.' : 'Um dos mundos que você ligou no seu atlas.') }}
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:10px">
                            <a href="{{ route('mundos-de-mim.galeria.index') }}" class="mm-btn mm-btn-primario">Abrir no atlas</a>
                            <a href="{{ $ultima->image_url }}" target="_blank" rel="noopener" download class="mm-btn mm-btn-contorno">Baixar em HD</a>
                        </div>
                    @else
                        <h2 class="mm-display" style="margin:0;font-size:36px;line-height:1.05;font-weight:800">Nenhum postal ainda</h2>
                        <p style="margin:0;font-size:16px;line-height:1.55;color:var(--mm-texto-suave)">Complete seu retrato e ligue alguns mundos. Os postais saem todo dia.</p>
                        <div><a href="{{ route('mundos-de-mim.perfil.index') }}" class="mm-btn mm-btn-primario">Fazer meu retrato</a></div>
                    @endif
                </div>
            </div>

            <div style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:24px">
                <div class="mm-card mm-card-lua" style="display:flex;flex-direction:column;gap:12px">
                    <span class="mm-rotulo">Seus extras · {{ $planoRotulo }}</span>
                    <div style="display:flex;align-items:baseline;gap:8px">
                        <span class="mm-display" style="font-size:64px;font-weight:800;line-height:1">{{ $stats['creditos'] ?? 0 }}</span>
                        <span style="font-size:16px;color:var(--mm-texto-papel)">{{ ($stats['creditos'] ?? 0) === 1 ? 'extra disponível' : 'extras disponíveis' }}</span>
                    </div>
                    <span style="font-size:14px;line-height:1.4;color:var(--mm-texto-papel)">Peça um postal na hora, além do que chega todo dia.</span>
                </div>

                <div class="mm-card" style="display:flex;flex-direction:column;gap:4px;flex-grow:1">
                    <span class="mm-rotulo" style="margin-bottom:8px">Para a próxima viagem</span>
                    <a href="{{ route('mundos-de-mim.perfil.index') }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--mm-linha)">
                        <span>Retrato</span>
                        @if($stats['perfil_ok'])
                            <span style="color:var(--mm-menta);font-size:14px;font-weight:600">Completo</span>
                        @else
                            <span style="color:var(--mm-poente);font-size:14px;font-weight:600">Falta contar como você é</span>
                        @endif
                    </a>
                    <a href="{{ route('mundos-de-mim.pessoas.index') }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--mm-linha)">
                        <span>Companhias</span>
                        <span style="color:var(--mm-texto-suave);font-size:14px">{{ $stats['pessoas_ativas'] }} {{ $stats['pessoas_ativas'] === 1 ? 'viajando' : 'viajando' }} com você</span>
                    </a>
                    <a href="{{ route('mundos-de-mim.estilos.index') }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 0">
                        <span>Mundos</span>
                        @if($stats['temas_sazonais'] > 0)
                            <span style="color:var(--mm-poente);font-size:14px;font-weight:600">{{ $stats['temas_sazonais'] }} de temporada aberto{{ $stats['temas_sazonais'] > 1 ? 's' : '' }}</span>
                        @else
                            <span style="color:var(--mm-texto-suave);font-size:14px">Explore a coleção permanente</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        @if($recentes->count() > 1)
            <section style="display:flex;flex-direction:column;gap:20px">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:8px">
                    <h2 class="mm-display" style="margin:0;font-size:32px;font-weight:700">Últimos postais</h2>
                    <a href="{{ route('mundos-de-mim.galeria.index') }}" style="padding:12px 0;font-size:15px;color:var(--mm-bruma);font-weight:600">Ver o atlas inteiro</a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px">
                    @foreach($recentes->skip(1) as $art)
                        <a href="{{ route('mundos-de-mim.galeria.index') }}" class="mm-postal" aria-label="{{ $art->theme?->name ?? 'Mundo surpresa' }}, {{ $art->reference_date->format('d/m') }}">
                            <img src="{{ $art->image_url }}" alt="" loading="lazy">
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-MundosDeMim::layout>
