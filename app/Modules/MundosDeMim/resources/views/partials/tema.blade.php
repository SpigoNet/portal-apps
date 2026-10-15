{{-- Tema "atlas pessoal" do Mundos de Mim. Compartilhado por layout, landing e auth. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --mm-noite: #14122B;
        --mm-crepusculo: #1E1B3F;
        --mm-linha: #3a366b;
        --mm-lua: #F3F0FB;
        --mm-poente: #FF7A59;
        --mm-bruma: #A79BFF;
        --mm-menta: #7FE0C2;
        --mm-texto-suave: #B4B0D4;
        --mm-texto-papel: #4A4672;
    }
    [x-cloak] { display: none !important; }
    body.mm { margin: 0; background: var(--mm-noite); color: var(--mm-lua); font-family: 'Instrument Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
    .mm a { color: inherit; text-decoration: none; }
    .mm-display { font-family: 'Bricolage Grotesque', system-ui, sans-serif; letter-spacing: -0.03em; }
    .mm-h1 { font-family: 'Bricolage Grotesque', system-ui, sans-serif; font-weight: 800; font-size: clamp(2.25rem, 5vw, 3.5rem); line-height: 1; letter-spacing: -0.03em; margin: 0; }
    .mm-lead { font-size: 1.125rem; line-height: 1.5; color: var(--mm-texto-suave); margin: 0; }
    .mm-wrap { max-width: 1280px; margin: 0 auto; padding: 48px 24px 72px; }
    .mm-wrap-estreito { max-width: 760px; }
    .mm-stack { display: flex; flex-direction: column; gap: 40px; }

    .mm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; border-radius: 999px; font: 600 15px 'Instrument Sans', sans-serif; border: 1px solid transparent; cursor: pointer; text-align: center; transition: transform .15s, background .15s; }
    .mm-btn:hover { transform: translateY(-1px); }
    .mm-btn:focus-visible, .mm-input:focus-visible { outline: 3px solid var(--mm-bruma); outline-offset: 2px; }
    .mm-btn-primario { background: var(--mm-poente); color: var(--mm-noite); }
    .mm-btn-lua { background: var(--mm-lua); color: var(--mm-noite); }
    .mm-btn-escuro { background: var(--mm-noite); color: var(--mm-lua); }
    .mm-btn-bruma { background: var(--mm-bruma); color: var(--mm-noite); }
    .mm-btn-contorno { background: none; color: var(--mm-lua); border-color: #6a64b0; }
    .mm-btn-bloco { width: 100%; }
    .mm-btn[disabled] { opacity: .6; cursor: wait; }

    .mm-card { background: var(--mm-crepusculo); border-radius: 28px; padding: 28px; }
    .mm-card-lua { background: var(--mm-lua); color: var(--mm-noite); }
    .mm-rotulo { font-size: 13px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--mm-texto-suave); }
    .mm-card-lua .mm-rotulo { color: var(--mm-texto-papel); }
    .mm-chip { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; background: var(--mm-noite); }
    .mm-chip-ok { color: var(--mm-menta); }
    .mm-chip-alerta { color: var(--mm-poente); }

    .mm-campo { display: flex; flex-direction: column; gap: 8px; }
    .mm-campo > label, .mm-rotulo-campo { font-size: 17px; font-weight: 600; }
    .mm-dica { font-size: 14px; line-height: 1.4; color: var(--mm-texto-suave); }
    body .mm-input { padding: 14px 16px; background: var(--mm-noite); color: var(--mm-lua); border: 1px solid var(--mm-linha); border-radius: 14px; font: 400 16px/1.5 'Instrument Sans', sans-serif; width: 100%; box-sizing: border-box; box-shadow: none; }
    body .mm-input::placeholder { color: #8F8BB8; }
    body .mm-input:focus { border-color: var(--mm-bruma); box-shadow: none; }
    body select.mm-input { appearance: none; }
    .mm-opcao { display: flex; gap: 12px; align-items: center; padding: 12px 0; font-size: 16px; cursor: pointer; }
    .mm-opcao input { width: 22px; height: 22px; accent-color: var(--mm-poente); flex: none; }
    .mm-opcao[aria-disabled="true"] { opacity: .5; cursor: not-allowed; }

    .mm-alerta { padding: 16px 20px; border-radius: 16px; font-size: 15px; line-height: 1.5; }
    .mm-alerta-ok { background: rgba(127,224,194,.12); border: 1px solid rgba(127,224,194,.5); color: var(--mm-menta); }
    .mm-alerta-erro { background: rgba(255,122,89,.12); border: 1px solid rgba(255,122,89,.55); color: #FFB09B; }
    .mm-alerta ul { margin: 6px 0 0; padding-left: 20px; }

    .mm-postal { position: relative; aspect-ratio: 3 / 4; border-radius: 20px; border: 5px solid var(--mm-lua); box-sizing: border-box; overflow: hidden; background: var(--mm-crepusculo); }
    .mm-postal img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .mm-grade-postais { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px 20px; }

    .mm-nav-link { padding: 12px 16px; border-radius: 999px; font-size: 15px; color: #D6D3EE; white-space: nowrap; background: none; border: 0; cursor: pointer; font-family: inherit; }
    .mm-nav-link:hover { background: rgba(255,255,255,.06); }
    .mm-nav-link[aria-current="page"] { background: var(--mm-lua); color: var(--mm-noite); font-weight: 600; }
    .mm-menu { position: absolute; top: 100%; left: 0; z-index: 50; min-width: 230px; margin-top: 6px; background: var(--mm-crepusculo); border: 1px solid var(--mm-linha); border-radius: 16px; padding: 8px; display: flex; flex-direction: column; }
    .mm-menu a, .mm-menu button { padding: 12px 14px; border-radius: 10px; font-size: 15px; text-align: left; background: none; border: 0; color: inherit; font-family: inherit; cursor: pointer; width: 100%; }
    .mm-menu a:hover, .mm-menu button:hover { background: rgba(255,255,255,.06); }

    /* Telas administrativas e Ateliê: papel claro, mantendo a moldura da marca */
    .mm-papel { background: var(--mm-lua); color: var(--mm-noite); min-height: 70vh; }
    .mm-papel-cabecalho { background: #fff; border-bottom: 1px solid #d9d4ee; }

    .mm-barra { display: none; flex-grow: 1; flex-wrap: wrap; gap: 12px 28px; align-items: center; width: 100%; }
    .mm-barra-aberta { display: flex; }
    @media (min-width: 768px) { .mm-barra { display: flex; width: auto; } .mm-hamburguer { display: none; } }


    /* Telas administrativas e Ateliê: remapeia as cores genéricas do Tailwind para a paleta da marca */
    .mm-papel { font-family: 'Instrument Sans', system-ui, sans-serif; }
    .mm-papel h1, .mm-papel h2, .mm-papel h3 { font-family: 'Bricolage Grotesque', system-ui, sans-serif; letter-spacing: -0.02em; color: var(--mm-noite); }
    .mm-papel .bg-white { border-radius: 20px; }
    .mm-papel .shadow, .mm-papel .shadow-sm, .mm-papel .shadow-md, .mm-papel .shadow-lg { box-shadow: none; }
    .mm-papel .bg-indigo-600, .mm-papel .bg-indigo-700, .mm-papel .bg-blue-500, .mm-papel .bg-blue-600, .mm-papel .bg-blue-700,
    .mm-papel .hover\:bg-indigo-700:hover, .mm-papel .hover\:bg-indigo-500:hover, .mm-papel .hover\:bg-blue-700:hover { background-color: var(--mm-poente) !important; color: var(--mm-noite) !important; border-radius: 999px; font-weight: 600; }
    .mm-papel .bg-indigo-600:hover, .mm-papel .bg-blue-500:hover, .mm-papel .bg-blue-600:hover { filter: brightness(.95); }
    .mm-papel .bg-indigo-50, .mm-papel .bg-indigo-100, .mm-papel .bg-blue-50, .mm-papel .bg-blue-100 { background-color: #E6E1F7 !important; }
    .mm-papel .text-indigo-500, .mm-papel .text-indigo-600, .mm-papel .text-indigo-700, .mm-papel .text-indigo-900, .mm-papel .text-blue-500, .mm-papel .text-blue-700, .mm-papel .text-blue-800 { color: #4B3FA8 !important; }
    .mm-papel .bg-indigo-600 .text-indigo-600 { color: inherit !important; }
    .mm-papel .border-indigo-100, .mm-papel .border-indigo-200, .mm-papel .border-indigo-300 { border-color: #cfc8ee !important; }
    .mm-papel .border-indigo-500, .mm-papel .ring-indigo-500 { border-color: var(--mm-bruma) !important; --tw-ring-color: var(--mm-bruma) !important; }
    .mm-papel .bg-red-600, .mm-papel .bg-red-700 { background-color: #C2410C !important; color: #fff !important; border-radius: 999px; }
    .mm-papel input[type="text"], .mm-papel input[type="email"], .mm-papel input[type="url"], .mm-papel input[type="number"], .mm-papel input[type="date"], .mm-papel input[type="password"], .mm-papel select, .mm-papel textarea { border-color: #cfc8ee; border-radius: 12px; }
    .mm-papel input:focus, .mm-papel select:focus, .mm-papel textarea:focus { border-color: var(--mm-bruma); --tw-ring-color: var(--mm-bruma); }
    .mm-papel input[type="checkbox"], .mm-papel input[type="radio"] { accent-color: var(--mm-poente); color: var(--mm-poente); }
    .mm-papel table thead { background: #E6E1F7 !important; }
    .mm-papel .container-admin { max-width: 1280px; margin: 0 auto; }

    .mm .glass { background: var(--mm-crepusculo); border-color: var(--mm-linha); backdrop-filter: none; }
    .mm .glass .text-slate-400 { color: var(--mm-texto-suave); }
    .mm-pagina .p-6 { max-width: 1280px; margin: 0 auto; }

    /* Aparelhos sem mouse: o que só aparecia ao passar o mouse fica sempre visível */
    @media (hover: none) {
        .mm [class*="group-hover"].opacity-0 { opacity: 1 !important; inset: auto 0 0 0 !important; height: auto !important; padding: 10px; background: linear-gradient(transparent, rgba(20,18,43,.9)) !important; justify-content: flex-end !important; align-items: flex-end !important; gap: 10px; }
        .mm button, .mm .mm-btn, .mm select { min-height: 44px; }
        .mm [class*="group-hover"] button[title] { min-width: 44px; }
    }
    @media (max-width: 767px) {
        .mm-wrap { padding: 32px 16px 56px; }
        .mm-card { padding: 20px; border-radius: 22px; }
        .mm-papel table { display: block; overflow-x: auto; white-space: nowrap; }
        .mm-papel .max-w-7xl, .mm-papel .max-w-5xl, .mm-papel .max-w-4xl { padding-left: 4px; padding-right: 4px; }
    }

    .mm .hidden { display: none !important; }
    .mm-sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
    .mm-pagina nav[role="navigation"] svg { width: 20px; height: 20px; }
    .mm-pagina nav[role="navigation"] a, .mm-pagina nav[role="navigation"] span[aria-current] > span { color: var(--mm-lua); }
    .mm-pagina nav[role="navigation"] p { color: var(--mm-texto-suave); }
    .mm-pagina nav[role="navigation"] a, .mm-pagina nav[role="navigation"] span > span { background: var(--mm-crepusculo) !important; border-color: var(--mm-linha) !important; }
    .mm-pagina nav[role="navigation"] [aria-current="page"] > span { background: var(--mm-poente) !important; color: var(--mm-noite) !important; }

    /* Cenas desenhadas em CSS: usadas quando ainda não há arte real */
    .mm-cena-1 { background: radial-gradient(circle at 68% 30%, #FFE3B8 0 7%, transparent 7.5%), radial-gradient(ellipse 80% 40% at 20% 100%, #12102A 0 98%, transparent 100%), radial-gradient(ellipse 90% 35% at 85% 100%, #1E1B3F 0 98%, transparent 100%), linear-gradient(180deg, #4B3FA8 0%, #FF7A59 78%); }
    .mm-cena-2 { background: radial-gradient(circle at 30% 24%, #F3F0FB 0 6%, transparent 6.5%), radial-gradient(ellipse 70% 50% at 70% 100%, #0E3B4A 0 98%, transparent 100%), radial-gradient(ellipse 60% 30% at 10% 100%, #14122B 0 98%, transparent 100%), linear-gradient(180deg, #14122B 0%, #3DB5C2 85%); }
    .mm-cena-3 { background: radial-gradient(circle at 50% 36%, #FFE3B8 0 10%, transparent 10.5%), radial-gradient(ellipse 100% 28% at 50% 100%, #C2410C 0 98%, transparent 100%), linear-gradient(180deg, #FFB48A 0%, #FF7A59 60%); }
    .mm-cena-4 { background: radial-gradient(circle at 74% 22%, #FFE3B8 0 5%, transparent 5.5%), radial-gradient(ellipse 80% 38% at 80% 100%, #14122B 0 98%, transparent 100%), radial-gradient(ellipse 60% 26% at 10% 100%, #1E1B3F 0 98%, transparent 100%), linear-gradient(180deg, #2B2670 0%, #A79BFF 80%); }
</style>
