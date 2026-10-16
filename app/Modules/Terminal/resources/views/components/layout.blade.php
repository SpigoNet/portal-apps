<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Terminal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-pwa-head start-link="/terminal" />
    <link rel="preload" href="/fonts/vt323-latin.woff2" as="font" type="font/woff2" crossorigin>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        @font-face {
            font-family: 'VT323'; font-style: normal; font-weight: 400; font-display: swap;
            src: url('/fonts/vt323-latin.woff2') format('woff2');
        }
        [x-cloak] { display: none !important; }
        /* O fundo fica no <html> para cobrir também a área sob o indicador de início do iPhone em tela cheia. */
        html { height: 100%; background-color: #120d09; }
        body { margin: 0; height: 100%; background: radial-gradient(ellipse at 50% 30%, #2a2016 0%, #120d09 75%); font-family: 'VT323', monospace; }
        html, body { overscroll-behavior: none; -webkit-tap-highlight-color: transparent; }
        body { touch-action: manipulation; -webkit-user-select: none; user-select: none; }

        /* Fósforos: o jogo troca a classe ph-* no container. */
        .ph-verde  { --ph: #52f59b; --ph-dim: #2fa86a; --bg: #03120a; --glow: rgba(82,245,155,.55); --veil: rgba(3,18,10,.92); }
        .ph-ambar  { --ph: #ffb742; --ph-dim: #b97f24; --bg: #140b02; --glow: rgba(255,183,66,.5);  --veil: rgba(20,11,2,.92); }
        .ph-branco { --ph: #e8efe9; --ph-dim: #9aa39c; --bg: #0c0d0c; --glow: rgba(232,239,233,.4); --veil: rgba(12,13,12,.92); }
        .ph-ciano    { --ph: #5eeaff; --ph-dim: #2b99ab; --bg: #031218; --glow: rgba(94,234,255,.5);  --veil: rgba(3,18,24,.92); }
        .ph-vermelho { --ph: #ff6b5e; --ph-dim: #a8372e; --bg: #160504; --glow: rgba(255,107,94,.5);  --veil: rgba(22,5,4,.92); }
        /* Papel térmico: tela clara, sem brilho. */
        .ph-papel    { --ph: #2b2a26; --ph-dim: #7a756a; --bg: #ece4cf; --glow: rgba(43,42,38,.12);  --veil: rgba(236,228,207,.94); }

        @keyframes crt-flick { 0%, 100% { opacity: 1; } 50% { opacity: .965; } }
        @keyframes crt-blink { 0%, 49% { opacity: 1; } 50%, 100% { opacity: 0; } }
        .crt-flick { animation: crt-flick .14s infinite; }
        .crt-blink { animation: crt-blink 1s steps(1) infinite; }
        .t-btn { font-family: inherit; color: var(--ph); background: transparent; border: 1px dashed var(--ph-dim); cursor: pointer; text-shadow: inherit; }
        .t-btn:not(:disabled):hover { background: var(--ph); color: var(--bg); text-shadow: none; }
        .t-btn:focus-visible { outline: 2px solid var(--ph); outline-offset: 2px; }
        /* Botão em forma de ônibus: frente arredondada à direita e duas rodas sobre a borda de baixo (não aumentam o botão). */
        .t-bus-btn { position: relative; border-radius: 3px 14px 3px 3px; margin-bottom: 5px; }
        .t-bus-btn::before, .t-bus-btn::after { content: ''; position: absolute; bottom: -5px; width: 7px; height: 7px; box-sizing: border-box; border-radius: 50%; border: 1px solid var(--ph-dim); background: var(--bg); }
        .t-bus-btn::before { left: 14%; }
        .t-bus-btn::after { right: 18%; }
        .t-bus-btn:not(:disabled):hover::before, .t-bus-btn:not(:disabled):hover::after { border-color: var(--ph); }
        /* Telas cheias (home, config, rank...): o x-show apaga display inline, por isso o flex fica na classe. */
        .t-scr { display: flex; flex-direction: column; }
        /* Tela inicial: coluna única em retrato; em paisagem, menu à esquerda e ranking à direita. */
        .t-home { display: flex; flex-direction: column; gap: 10px; }
        .t-home-esq { display: flex; flex-direction: column; gap: 10px; }
        .t-home-dir { flex: 1; min-height: 0; display: flex; flex-direction: column; }
        @media (min-aspect-ratio: 1/1) and (min-width: 600px) {
            .t-home { flex-direction: row; gap: 16px; }
            .t-home-esq { width: 42%; flex: none; justify-content: space-around; }
        }
        .bus-art { margin: 0; font-family: inherit; font-size: 18px; line-height: 16px; white-space: pre; }
        @media (prefers-reduced-motion: reduce) { .crt-flick, .crt-blink { animation: none; } }

        /* Paisagem: duas colunas (passageiros, ônibus e console à esquerda; vagas à direita). O teclado só cabe em telas altas. */
        @media (min-aspect-ratio: 1/1) and (min-width: 600px) {
            .t-raiz { max-width: 1100px !important; padding-left: calc(10px + env(safe-area-inset-left)) !important; padding-right: calc(10px + env(safe-area-inset-right)) !important; }
            .t-monitor { padding: 8px !important; border-radius: 18px 18px 8px 8px !important; }
            .t-tela { display: grid !important; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); grid-template-rows: auto auto auto auto 1fr; column-gap: 16px; row-gap: 8px; }
            .t-cab { grid-column: 1 / -1; }
            .t-pass, .t-onib { grid-column: 1; }
            .t-cons { grid-column: 1; grid-row: 5; align-self: end; }
            .t-vagas { grid-column: 2; grid-row: 3 / 6; border-left: 1px dashed var(--ph-dim); padding-left: 16px; }
        }
        @media (min-aspect-ratio: 1/1) and (min-width: 600px) and (max-height: 559px) {
            .t-teclado { display: none !important; }
        }
    </style>
    <script>
        // iOS ignora parte do viewport/touch-action: bloqueia o zoom por toque duplo e por pinça.
        (function () {
            var ultimo = 0;
            document.addEventListener('touchend', function (e) {
                var agora = Date.now();
                if (agora - ultimo < 350) e.preventDefault();
                ultimo = agora;
            }, { passive: false });
            ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (nome) {
                document.addEventListener(nome, function (e) { e.preventDefault(); });
            });
            document.addEventListener('dblclick', function (e) { e.preventDefault(); });
        })();
    </script>
</head>
<body>
    {{ $slot }}
</body>
</html>
