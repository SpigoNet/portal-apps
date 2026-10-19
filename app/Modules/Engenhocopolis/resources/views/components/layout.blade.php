<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Engenhocópolis</title>
    <x-pwa-head start-link="/engenhocopolis" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --fundo: #1b232d; --painel: #253140; --painel-2: #2f3d4f; --borda: #3d4e63;
            --texto: #eef2f6; --texto-2: #a9b6c6; --laranja: #f39a2e; --laranja-2: #d97c12;
            --verde: #4cc46a; --vermelho: #ef5a4c; --amarelo: #f6c445; --azul: #59a7ff;
        }
        * { box-sizing: border-box; }
        html { height: 100%; background: var(--fundo); }
        body { margin: 0; height: 100%; color: var(--texto); font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: var(--fundo); }
        html, body { overscroll-behavior: none; -webkit-tap-highlight-color: transparent; }
        body { -webkit-user-select: none; user-select: none; }
        button { font: inherit; color: inherit; }

        .app { height: 100%; display: flex; flex-direction: column; }

        /* Botões genéricos */
        .bt { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 44px; padding: 0 14px; border-radius: 12px; border: 1px solid var(--borda); background: var(--painel-2); cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap; }
        .bt:hover:not(:disabled) { border-color: var(--texto-2); }
        .bt:disabled { opacity: .4; cursor: default; }
        .bt:focus-visible { outline: 3px solid var(--amarelo); outline-offset: 2px; }
        .bt-primario { background: linear-gradient(#f7a943, var(--laranja-2)); border-color: #ffbe6b; color: #2a1600; }
        .bt-verde { background: linear-gradient(#5fd57c, #2f9e4c); border-color: #8de8a3; color: #062710; }
        .bt-icone { width: 44px; padding: 0; font-size: 18px; }
        .bt-ativo { border-color: var(--amarelo) !important; box-shadow: 0 0 0 2px var(--amarelo) inset; }

        /* ---------- Mapa (seleção de fases) ---------- */
        .mapa { flex: 1; overflow-y: auto; padding: calc(16px + env(safe-area-inset-top)) 16px calc(24px + env(safe-area-inset-bottom));
            background: radial-gradient(ellipse at 50% -10%, #34506e 0%, var(--fundo) 60%); }
        .mapa-int { max-width: 880px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 4px; margin: 6px 0 0; font-size: clamp(30px, 8vw, 54px); font-weight: 900; letter-spacing: .5px;
            color: #ffc04d; text-shadow: 0 3px 0 #9a4f06, 0 6px 14px rgba(0,0,0,.5); -webkit-text-stroke: 1.5px #4a2603; }
        .logo svg { width: .95em; height: .95em; filter: drop-shadow(0 3px 0 #1d3a63); }
        .subtitulo { text-align: center; color: var(--texto-2); margin: -8px 0 0; font-size: 15px; }
        .setor h2 { margin: 0 0 8px; font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--texto-2); }
        .grade-fases { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .cartao-fase { position: relative; text-align: left; min-height: 96px; padding: 12px; border-radius: 16px; border: 2px solid var(--borda); background: var(--painel); cursor: pointer; display: flex; flex-direction: column; gap: 6px; }
        .cartao-fase:hover:not(:disabled) { border-color: var(--laranja); }
        .cartao-fase:disabled { cursor: default; opacity: .55; }
        .cartao-fase.atual { border-color: var(--laranja); box-shadow: 0 0 0 3px rgba(243,154,46,.25); }
        .num-fase { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; background: var(--laranja); color: #2a1600; }
        .cartao-fase:disabled .num-fase { background: var(--painel-2); color: var(--texto-2); }
        .nome-fase { font-weight: 700; font-size: 14px; line-height: 1.2; }
        .medalhas { display: flex; gap: 4px; }
        .medalha { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; background: #1e2833; color: #4c5a6b; border: 1px solid #36465a; }
        .medalha.on { background: #ffd66b; color: #5a3800; border-color: #ffe7a8; }
        .caixa-info { background: var(--painel); border: 1px solid var(--borda); border-radius: 16px; padding: 14px 16px; color: var(--texto-2); font-size: 14px; line-height: 1.5; }
        .caixa-info b { color: var(--texto); }
        .linha-prefs { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }

        /* ---------- Jogo ---------- */
        .jogo { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr) auto; }
        .jogo > * { min-width: 0; }
        .topo { display: flex; align-items: center; gap: 10px; padding: calc(8px + env(safe-area-inset-top)) calc(10px + env(safe-area-inset-right)) 8px calc(10px + env(safe-area-inset-left)); background: var(--painel); border-bottom: 1px solid var(--borda); }
        .topo-titulo { flex: 1; min-width: 0; }
        .topo-titulo b { display: block; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topo-titulo span { display: block; font-size: 12.5px; color: var(--texto-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .progresso { font-weight: 800; font-size: 14px; padding: 6px 10px; border-radius: 10px; background: #1e2833; border: 1px solid var(--borda); white-space: nowrap; }
        .palco { position: relative; min-height: 0; overflow: hidden; background: radial-gradient(ellipse at 50% 40%, #3b4b5e 0%, #222c38 75%); }
        .tabuleiro { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; display: block; cursor: default; }
        .tabuleiro.panando { cursor: grabbing; }
        .camera { position: absolute; right: 10px; top: 10px; display: flex; flex-direction: column; gap: 6px; }
        .camera .bt { background: rgba(37,49,64,.92); }
        .cartao { position: absolute; background: rgba(37,49,64,.97); border: 1px solid var(--borda); border-radius: 16px; padding: 12px 14px; box-shadow: 0 10px 30px rgba(0,0,0,.35); }
        .cartao h3 { margin: 0 0 4px; font-size: 15px; }
        .cartao p { margin: 0 0 8px; font-size: 13.5px; line-height: 1.45; color: var(--texto-2); }
        /* Peça selecionada: barra de botões só com ícones; o texto completo abre no botão de informações. */
        .inspetor { position: absolute; left: 10px; bottom: 10px; right: 66px; pointer-events: none; }
        .inspetor-int { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
        .inspetor-int > * { pointer-events: auto; }
        .info-peca { position: relative; width: min(400px, 100%); max-height: 46vh; overflow-y: auto; }
        .info-peca p:last-child { margin-bottom: 0; }
        .info-atual { color: var(--texto) !important; font-weight: 600; }
        .barra-peca { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 6px; border-radius: 18px; background: rgba(37,49,64,.96); border: 1px solid var(--borda); box-shadow: 0 10px 30px rgba(0,0,0,.35); }
        .grupo-param, .grupo-acoes { display: flex; align-items: center; gap: 6px; }
        .grupo-param { padding: 3px; border-radius: 16px; background: rgba(0,0,0,.18); }
        .bi { width: 56px; height: 56px; border-radius: 14px; border: 1px solid var(--borda); background: var(--painel-2); color: var(--texto); display: grid; place-items: center; cursor: pointer; padding: 0; }
        .bi svg { width: 28px; height: 28px; }
        .bi:hover:not(:disabled) { border-color: var(--texto-2); }
        .bi:disabled { opacity: .35; cursor: default; }
        .bi:focus-visible { outline: 3px solid var(--amarelo); outline-offset: 2px; }
        .bi.on { background: var(--laranja); border-color: #ffbe6b; color: #2a1600; }
        .bi-perigo:hover:not(:disabled) { color: var(--vermelho); border-color: var(--vermelho); }
        .bi-fixa { cursor: default; color: var(--texto-2); }
        .valor { min-width: 48px; text-align: center; font-size: 24px; font-weight: 900; }
        /* Abertura do separador: P, M e G; os que caem descem e ficam roxos. */
        .vao { display: flex; align-items: flex-start; gap: 4px; height: 56px; padding: 6px 4px 0; border-bottom: 3px dashed #59636f; }
        .vao i { display: grid; place-items: center; border-radius: 4px; background: #5b6675; color: #fff; font-style: normal; font-size: 11px; font-weight: 900; transition: transform .2s ease, background .2s; box-shadow: inset 0 -3px 0 rgba(0,0,0,.25); }
        .vao i.cai { background: #8f5fd8; transform: translateY(18px); }
        @media (prefers-reduced-motion: reduce) { .vao i { transition: none; } }
        .intro { left: 50%; top: 12px; transform: translateX(-50%); width: min(460px, calc(100% - 80px)); }
        .resultado { left: 50%; top: 12px; transform: translateX(-50%); width: min(480px, calc(100% - 20px)); z-index: 2; }
        .resultado.ok { border-color: var(--verde); }
        .resultado.erro { border-color: var(--vermelho); }
        .acoes { display: flex; flex-wrap: wrap; gap: 8px; }
        .passo { display: flex; align-items: center; gap: 8px; margin: 6px 0 8px; }
        .passo output { min-width: 34px; text-align: center; font-size: 20px; font-weight: 800; }
        .passo .rotulo { flex: 1; font-size: 13px; color: var(--texto-2); }
        .seg { display: inline-flex; border: 1px solid var(--borda); border-radius: 10px; overflow: hidden; }
        .seg button { min-height: 38px; padding: 0 12px; border: 0; background: var(--painel-2); cursor: pointer; font-size: 13px; font-weight: 600; }
        .seg button.on { background: var(--laranja); color: #2a1600; }

        .rodape { background: var(--painel); border-top: 1px solid var(--borda); padding: 8px calc(10px + env(safe-area-inset-right)) calc(8px + env(safe-area-inset-bottom)) calc(10px + env(safe-area-inset-left)); display: flex; flex-direction: column; gap: 8px; }
        .fila { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: thin; }
        .fila-rotulo { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--texto-2); align-self: center; padding-right: 2px; white-space: nowrap; }
        .pac { flex: none; display: flex; align-items: center; gap: 4px; padding: 3px 7px 3px 4px; border-radius: 9px; background: #1e2833; border: 1px solid var(--borda); font-size: 12.5px; font-weight: 700; }
        .pac i { display: grid; place-items: center; border-radius: 4px; color: #fff; font-style: normal; font-size: 11px; font-weight: 800; box-shadow: inset 0 -3px 0 rgba(0,0,0,.25); }
        .pac.ok { border-color: var(--verde); opacity: .55; }
        .pac.erro { border-color: var(--vermelho); box-shadow: 0 0 0 2px var(--vermelho); }
        .ferramentas { display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch; }
        .inventario { display: flex; gap: 6px; overflow-x: auto; flex: 1 1 100%; min-width: 0; }
        .comandos { flex: 1 1 100%; }
        .peca-bt { position: relative; flex: none; width: 66px; height: 62px; border-radius: 12px; border: 1px solid var(--borda); background: var(--painel-2); cursor: pointer; padding: 0; display: grid; place-items: center; }
        .peca-bt svg { width: 56px; height: 48px; }
        .peca-bt:disabled { opacity: .35; cursor: default; }
        .peca-bt .qtd { position: absolute; right: 3px; bottom: 2px; font-size: 12px; font-weight: 800; background: #1b232d; border-radius: 7px; padding: 0 5px; }
        .peca-bt.on { border-color: var(--amarelo); box-shadow: 0 0 0 2px var(--amarelo) inset; background: #3a4a5f; }
        .comandos { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }

        @keyframes pulsar { 0%, 100% { opacity: .9; } 50% { opacity: .35; } }
        .pulsar { animation: pulsar 1s ease-in-out infinite; }
        @keyframes surgir { from { opacity: 0; transform: translate(-50%, -8px); } to { opacity: 1; transform: translate(-50%, 0); } }
        .resultado, .intro { animation: surgir .25s ease-out; }
        .ajuda { position: fixed; inset: 0; z-index: 10; background: rgba(10,14,20,.7); display: grid; place-items: center; padding: 16px; }
        .ajuda .cartao { position: relative; max-width: 520px; max-height: 100%; overflow-y: auto; }
        .ajuda ul { margin: 0 0 10px; padding-left: 18px; color: var(--texto-2); font-size: 13.5px; line-height: 1.5; }

        /* Paisagem larga: inventário e comandos numa coluna à direita, como no conceito. */
        @media (min-width: 900px) and (min-aspect-ratio: 1/1) {
            .jogo { grid-template-columns: minmax(0, 1fr) 300px; grid-template-rows: auto minmax(0, 1fr); }
            .topo { grid-column: 1 / -1; }
            .rodape { border-top: 0; border-left: 1px solid var(--borda); overflow-y: auto; padding-bottom: calc(12px + env(safe-area-inset-bottom)); }
            .fila { flex-wrap: wrap; overflow: visible; }
            .ferramentas { flex-direction: column; flex-wrap: nowrap; }
            .inventario, .comandos { flex: none; }
            .inventario { flex-wrap: wrap; overflow: visible; }
            .comandos { justify-content: flex-start; }
        }
        @media (prefers-reduced-motion: reduce) { .pulsar, .resultado, .intro { animation: none; } }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
