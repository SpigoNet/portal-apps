<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14122B">
    <meta name="description" content="Todo dia, um postal de um mundo novo de você, direto no seu celular.">
    <title>Mundos de Mim · Todo dia, um mundo novo de você</title>
    @include('MundosDeMim::partials.tema')
    <style>
        html { scroll-behavior: smooth; }
        .ld-wrap { max-width: 1200px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
        .ld-titulo { font-family: 'Bricolage Grotesque', sans-serif; font-weight: 800; letter-spacing: -0.03em; line-height: 1.02; margin: 0; }
        .ld-passo { background: var(--mm-crepusculo); border-radius: 24px; padding: 32px; display: flex; flex-direction: column; gap: 14px; }
        .ld-passo b.n { font: 800 56px/1 'Bricolage Grotesque', sans-serif; }
        .ld-passo h3 { margin: 0; font: 700 26px/1.1 'Bricolage Grotesque', sans-serif; letter-spacing: -0.02em; }
        .ld-passo p { margin: 0; font-size: 16px; line-height: 1.55; color: var(--mm-texto-suave); }
        .ld-plano { flex: 1 1 320px; max-width: 480px; min-width: 0; border-radius: 32px; padding: 40px; display: flex; flex-direction: column; gap: 20px; position: relative; }
        .ld-plano ul { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 12px; font-size: 16px; flex-grow: 1; }
        .ld-plano .preco { display: flex; align-items: baseline; gap: 6px; }
        .ld-plano .preco b { font: 800 52px/1 'Bricolage Grotesque', sans-serif; letter-spacing: -0.03em; }
    </style>
</head>
<body class="mm">
    @php
        $cenas = ['mm-cena-1', 'mm-cena-2', 'mm-cena-3', 'mm-cena-4'];
        $destaques = array_slice($images ?? [], 0, 4);
    @endphp

    <header class="ld-wrap" style="padding-top:24px;padding-bottom:24px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between">
        <a href="#topo" aria-label="Mundos de Mim"><x-MundosDeMim::logo :tamanho="40" fonte="26px" /></a>
        <nav style="display:flex;flex-wrap:wrap;gap:8px;align-items:center" aria-label="Principal">
            <a href="#como" class="mm-nav-link">Como funciona</a>
            <a href="#planos" class="mm-nav-link">Planos</a>
            @auth
                <a href="{{ route('mundos-de-mim.index') }}" class="mm-btn mm-btn-primario">Abrir meu atlas</a>
            @else
                <a href="{{ route('mundos-de-mim.login') }}" class="mm-btn mm-btn-contorno">Entrar</a>
                <a href="{{ route('mundos-de-mim.register') }}" class="mm-btn mm-btn-primario">Criar meu atlas</a>
            @endauth
        </nav>
    </header>

    <main>
        <section id="topo" class="ld-wrap" style="padding-top:56px;padding-bottom:40px;display:flex;flex-wrap:wrap;gap:48px;align-items:center">
            <div style="flex:1 1 440px;min-width:0;display:flex;flex-direction:column;gap:28px">
                <span class="mm-chip" style="align-self:flex-start;background:var(--mm-crepusculo);color:var(--mm-bruma);padding:8px 16px;font-size:14px">Um postal novo, todo dia</span>
                <h1 class="ld-titulo" style="font-size:clamp(46px,7vw,88px)">Todo dia, um mundo novo de <span style="color:var(--mm-poente)">você</span>.</h1>
                <p class="mm-lead" style="font-size:20px;line-height:1.55;max-width:520px">Conte como você é e quem viaja com você. Toda manhã chega no seu celular um postal de um mundo onde você mora: um planeta de marés, uma cidade de aurora, um deserto de duas luas.</p>
                <div style="display:flex;flex-wrap:wrap;gap:12px">
                    <a href="#planos" class="mm-btn mm-btn-primario" style="padding:16px 28px;font-size:17px">Começar a viajar</a>
                    <a href="#como" class="mm-btn mm-btn-contorno" style="padding:16px 28px;font-size:17px">Ver como funciona</a>
                </div>
            </div>

            <div style="flex:1 1 380px;min-width:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px" aria-hidden="true">
                @foreach($cenas as $i => $cena)
                    <div class="mm-postal {{ isset($destaques[$i]) ? '' : $cena }}" style="border-radius:24px;border-width:6px;{{ ['', 'margin-top:48px', 'margin-top:-24px', 'margin-top:24px'][$i] }}">
                        @isset($destaques[$i])
                            <img src="{{ $destaques[$i] }}" alt="" loading="lazy">
                        @endisset
                    </div>
                @endforeach
            </div>
        </section>

        <section id="como" class="ld-wrap" style="padding-top:88px;padding-bottom:40px;display:flex;flex-direction:column;gap:40px">
            <h2 class="ld-titulo" style="font-size:clamp(34px,5vw,56px);max-width:720px">Três passos até o primeiro postal.</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px">
                <div class="ld-passo">
                    <b class="n" style="color:var(--mm-poente)">1</b>
                    <h3>Faça seu retrato</h3>
                    <p>Envie uma foto e descreva seus traços, seu jeito e o que você gosta. Se preferir, sugerimos uma primeira versão para você ajustar.</p>
                </div>
                <div class="ld-passo">
                    <b class="n" style="color:var(--mm-bruma)">2</b>
                    <h3>Escolha seus mundos</h3>
                    <p>Ligue os universos que combinam com você, dos de cada estação aos que ficam o ano todo. Chame quem viaja junto: parceiro, filhos, amigos.</p>
                </div>
                <div class="ld-passo">
                    <b class="n" style="color:var(--mm-menta)">3</b>
                    <h3>Receba o postal</h3>
                    <p>Todo dia uma arte nova chega no Telegram ou no WhatsApp e fica guardada no seu atlas, junto com todas as outras.</p>
                </div>
            </div>
        </section>

        <section class="ld-wrap" style="padding-top:56px;padding-bottom:56px;display:flex;flex-wrap:wrap;gap:32px;align-items:center">
            <div class="mm-cena-1" role="img" aria-label="Ilustração de um mundo ao entardecer" style="flex:1 1 360px;min-width:0;height:380px;border-radius:32px"></div>
            <div style="flex:1 1 360px;min-width:0;display:flex;flex-direction:column;gap:18px">
                <h2 class="ld-titulo" style="font-size:clamp(32px,4.5vw,48px)">Ninguém viaja sozinho.</h2>
                <p class="mm-lead" style="font-size:18px;line-height:1.6">Cadastre suas companhias e escolha quem aparece em cada mundo. Postais a dois, em família ou só seus.</p>
                <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px;font-size:17px">
                    <li style="display:flex;gap:12px;align-items:center"><span style="width:10px;height:10px;border-radius:50%;background:var(--mm-poente);flex:none"></span>Você decide quem pode aparecer, a qualquer hora</li>
                    <li style="display:flex;gap:12px;align-items:center"><span style="width:10px;height:10px;border-radius:50%;background:var(--mm-bruma);flex:none"></span>Mundos para crianças, jovens e adultos</li>
                    <li style="display:flex;gap:12px;align-items:center"><span style="width:10px;height:10px;border-radius:50%;background:var(--mm-menta);flex:none"></span>Diga o que nunca deve aparecer nos seus postais</li>
                </ul>
            </div>
        </section>

        <section id="planos" class="ld-wrap" style="padding-top:88px;padding-bottom:88px;display:flex;flex-direction:column;gap:40px">
            <div style="display:flex;flex-direction:column;gap:12px;text-align:center;align-items:center">
                <h2 class="ld-titulo" style="font-size:clamp(34px,5vw,56px)">Escolha como viajar.</h2>
                <p class="mm-lead">Os dois dão um postal por dia e 5 extras por semana. Muda o caminho até você.</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:24px;justify-content:center;align-items:stretch">
                <div class="ld-plano" style="background:var(--mm-crepusculo)">
                    <h3 class="mm-display" style="margin:0;font-size:30px;font-weight:700">Passagem</h3>
                    <div class="preco"><b>R$ 14,90</b><span style="color:var(--mm-texto-suave)">/mês</span></div>
                    <ul style="color:#D6D3EE">
                        <li>1 postal por dia</li><li>Entrega pelo Telegram</li><li>5 extras por semana, quando você quiser</li><li>Atlas completo online</li>
                    </ul>
                    <a href="{{ route('mundos-de-mim.register') }}" class="mm-btn mm-btn-contorno mm-btn-bloco" style="padding:16px">Começar com a Passagem</a>
                </div>
                <div class="ld-plano" style="background:var(--mm-lua);color:var(--mm-noite)">
                    <span class="mm-chip" style="position:absolute;top:28px;right:28px;background:var(--mm-poente);color:var(--mm-noite);font-size:12px">Mais escolhido</span>
                    <h3 class="mm-display" style="margin:0;font-size:30px;font-weight:700">Expedição</h3>
                    <div class="preco"><b>R$ 34,90</b><span style="color:var(--mm-texto-papel)">/mês</span></div>
                    <ul style="color:#2B2850">
                        <li>1 postal por dia</li><li>Entrega pelo WhatsApp</li><li>5 extras por semana, quando você quiser</li><li>Mundos de temporada premium</li><li>Atendimento prioritário</li>
                    </ul>
                    <a href="{{ route('mundos-de-mim.register') }}" class="mm-btn mm-btn-escuro mm-btn-bloco" style="padding:16px">Embarcar na Expedição</a>
                </div>
            </div>
        </section>
    </main>

    <footer style="border-top:1px solid #2c2860">
        <div class="ld-wrap" style="padding-top:32px;padding-bottom:32px;display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:center;font-size:14px;color:var(--mm-texto-suave)">
            <span>© {{ date('Y') }} Mundos de Mim · um app Spigo.net</span>
            <span style="display:flex;gap:8px;flex-wrap:wrap"><a href="#topo" style="padding:12px">Privacidade</a><a href="#topo" style="padding:12px">Termos</a><a href="#topo" style="padding:12px">Ajuda</a></span>
        </div>
    </footer>
</body>
</html>
