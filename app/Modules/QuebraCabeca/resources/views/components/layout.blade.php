<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Quebra-Cabeça</title>
    <x-pwa-head start-link="/quebra-cabeca" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* O fundo fica no <html> (e não só no <body>) para cobrir também a área sob o indicador de início
           do iPhone em tela cheia, que fica fora da altura do body. A cor de base é a do fim do degradê. */
        html { height: 100%; background-color: #4a044e;
               background-image: linear-gradient(to bottom, #1e1b4b, #581c87, #4a044e);
               background-repeat: no-repeat; background-size: 100% 100%; }
        body { height: 100%; background: transparent; }
        html, body { overscroll-behavior: none; -webkit-tap-highlight-color: transparent; }
        body { touch-action: manipulation; -webkit-user-select: none; user-select: none; }
        .btn { display: block; width: 100%; text-align: center; font-weight: 800; border-radius: 1rem; padding: .9rem 1.25rem;
               border-bottom: 5px solid rgba(0,0,0,.28); transition: transform .08s, border-bottom-width .08s; }
        .btn:active { transform: translateY(3px); border-bottom-width: 2px; }
        .btn:disabled { opacity: .45; }
        .btn-primary { background: #fbbf24; color: #3b0764; }
        .btn-green { background: #34d399; color: #022c22; }
        .btn-sec { background: rgba(255,255,255,.14); color: #fff; }
        .chip { padding: .35rem .9rem; border-radius: 999px; font-weight: 700; font-size: .85rem; white-space: nowrap;
                background: rgba(255,255,255,.12); color: #e9d5ff; }
        .chip.on { background: #fbbf24; color: #3b0764; }
        .card { background: rgba(255,255,255,.1); border-radius: 1.25rem; }
        .pop { animation: pop .35s cubic-bezier(.2,1.4,.4,1); }
        @keyframes pop { 0% { transform: scale(.6); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        /* Peça em alto relevo. Cada lado tem bisel e sombra próprios; um lado encostado numa vizinha
           na posição correta (classes j-t/j-r/j-b/j-l) perde bisel, sombra e arredondamento e as peças se fundem. */
        .peca { position: relative; overflow: hidden; border-radius: 7px; transition: transform .15s ease, box-shadow .15s ease;
                --ot: 0 -2px 4px -3px rgba(0,0,0,.5); --ol: -2px 0 4px -3px rgba(0,0,0,.5);
                --ob: 0 5px 6px -3px rgba(0,0,0,.65); --or: 5px 0 6px -3px rgba(0,0,0,.65);
                --bt: inset 0 3px 2px -1px rgba(255,255,255,.8); --bl: inset 3px 0 2px -1px rgba(255,255,255,.8);
                --bb: inset 0 -4px 4px -1px rgba(0,0,0,.65); --br: inset -4px 0 4px -1px rgba(0,0,0,.65);
                box-shadow: var(--ot), var(--ol), var(--ob), var(--or); }
        .peca::before { content: ''; position: absolute; inset: 0; pointer-events: none; border-radius: inherit;
                background: linear-gradient(135deg, rgba(255,255,255,.3) 0%, rgba(255,255,255,.05) 28%, rgba(0,0,0,0) 52%, rgba(0,0,0,.3) 100%); }
        .peca::after { content: ''; position: absolute; inset: 0; pointer-events: none; border-radius: inherit;
                box-shadow: var(--bt), var(--bl), var(--bb), var(--br); }
        .peca.j-t { --ot: 0 0 0 0 transparent; --bt: inset 0 0 0 0 transparent; border-top-left-radius: 0; border-top-right-radius: 0; }
        .peca.j-b { --ob: 0 0 0 0 transparent; --bb: inset 0 0 0 0 transparent; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
        .peca.j-l { --ol: 0 0 0 0 transparent; --bl: inset 0 0 0 0 transparent; border-top-left-radius: 0; border-bottom-left-radius: 0; }
        .peca.j-r { --or: 0 0 0 0 transparent; --br: inset 0 0 0 0 transparent; border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .peca.j-any::before { display: none; }
        .peca:active { transform: scale(.97); }
        .peca-sel { transform: translateY(-3px) scale(1.06); z-index: 10;
                box-shadow: 0 12px 16px rgba(0,0,0,.6), 0 3px 5px rgba(0,0,0,.6) !important; }
        .peca-sel::before { display: block !important; }
        .peca-sel::after { box-shadow: inset 3px 3px 3px rgba(255,255,255,.65), inset -4px -4px 5px rgba(0,0,0,.6) !important; }
        .peca-sel { border-radius: 7px !important; }
        .scroll-x { overflow-x: auto; scrollbar-width: none; }
        .scroll-x::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="antialiased text-white">
    {{ $slot }}
</body>
</html>
