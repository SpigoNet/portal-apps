<x-MundosDeMim::layout>
    <div class="mm-wrap mm-stack">

        <div style="display:flex;flex-direction:column;gap:10px;max-width:640px">
            <h1 class="mm-h1">Para onde você quer ir?</h1>
            <p class="mm-lead">Ligue os mundos que combinam com você. Seus postais só saem deles.</p>
        </div>

        @if(session('success'))
            <div role="status" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
                 class="mm-alerta mm-alerta-ok" style="position:fixed;bottom:16px;right:16px;z-index:60;max-width:calc(100% - 32px)">
                {{ session('success') }}
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
            @foreach($themes as $theme)
                @php
                    $isEnabled = in_array($theme->id, $userEnabledThemes);
                    $exampleUrls = $theme->examples->map(fn ($ex) => Storage::url($ex->image_path))->values()->toArray();
                @endphp

                <div x-data="{
                        images: {{ json_encode($exampleUrls) }},
                        active: 0,
                        timer: null,
                        start() { if (this.images.length > 1 && !this.timer) { this.timer = setInterval(() => { this.active = (this.active + 1) % this.images.length }, 1200) } },
                        stop() { clearInterval(this.timer); this.timer = null; this.active = 0 }
                    }"
                     @mouseenter="start()" @mouseleave="stop()" @focusin="start()" @focusout="stop()"
                     class="mm-card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;{{ $isEnabled ? 'box-shadow:0 0 0 2px var(--mm-poente)' : '' }}">

                    <div style="position:relative;height:340px;background:var(--mm-noite)">
                        @if(count($exampleUrls))
                            <img :src="images[active]" alt="Exemplo de postal do mundo {{ $theme->name }}" loading="lazy"
                                 style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="mm-cena-{{ ($theme->id % 4) + 1 }}" role="img" aria-label="Sem exemplo ainda" style="width:100%;height:100%"></div>
                        @endif

                        <span class="mm-chip" style="position:absolute;left:14px;top:14px">
                            {{ $theme->is_seasonal ? 'Temporada' : 'Sempre aberto' }}
                        </span>

                        @if($theme->example_input_path)
                            <div style="position:absolute;left:14px;bottom:14px;width:84px;height:84px;border-radius:16px;border:3px solid var(--mm-lua);overflow:hidden;background:var(--mm-lua)">
                                <img src="{{ Storage::url($theme->example_input_path) }}" alt="Foto de referência usada no exemplo" style="width:100%;height:100%;object-fit:cover">
                            </div>
                        @endif
                    </div>

                    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;flex-grow:1">
                        <div>
                            <h3 class="mm-display" style="margin:0;font-size:24px;font-weight:700">{{ $theme->name }}</h3>
                            @if($theme->example_input_description)
                                <p style="margin:6px 0 0;font-size:14px;line-height:1.45;color:var(--mm-texto-suave)">Pede: {{ $theme->example_input_description }}</p>
                            @endif
                        </div>

                        <form action="{{ route('mundos-de-mim.estilos.toggle') }}" method="POST" style="margin-top:auto">
                            @csrf
                            <input type="hidden" name="theme_id" value="{{ $theme->id }}">
                            @if($isEnabled)
                                <button type="submit" class="mm-btn mm-btn-primario mm-btn-bloco" aria-pressed="true">Ligado · toque para desligar</button>
                            @else
                                <button type="submit" class="mm-btn mm-btn-contorno mm-btn-bloco" aria-pressed="false">Ligar este mundo</button>
                            @endif
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @if($themes->isEmpty())
            <div class="mm-card" style="text-align:center;color:var(--mm-texto-suave)">Nenhum mundo aberto agora. Volte em breve.</div>
        @endif
    </div>
</x-MundosDeMim::layout>
