{{-- Visual do TreeTask (marca Spigo.net). Só classes com prefixo tt-/st-/p-/chip-: não depende do Tailwind. --}}
<style>
    :root {
        --tt-ink: #1E1627;
        --tt-muted: #655A70;
        --tt-line: #E7E1EE;
        --tt-line-2: #F0EBF4;
        --tt-bg: #F7F4FA;
        --tt-brand: #7B4FA8;
        --tt-brand-d: #653B92;
        --tt-brand-soft: #F2EBF8;
        --tt-lime: #C9E86B;
        --tt-shadow: 0 1px 2px rgba(35, 27, 44, .04), 0 10px 30px rgba(35, 27, 44, .04);
    }

    [x-cloak] { display: none !important; }

    .tt-body { margin: 0; background: var(--tt-bg); color: var(--tt-ink); font-family: 'Hanken Grotesk', system-ui, sans-serif; font-size: 14px; line-height: 1.45; -webkit-font-smoothing: antialiased; }
    .tt-body *, .tt-body *::before, .tt-body *::after { box-sizing: border-box; }
    .tt-body h1, .tt-body h2, .tt-body h3 { font-family: 'Montserrat', 'Hanken Grotesk', system-ui, sans-serif; margin: 0; }
    .tt-body a { color: inherit; }
    .tt-body button { font-family: inherit; }
    .tt-i { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; flex: none; }
    .tt-i.s { width: 15px; height: 15px; }
    .tt-i.l { width: 22px; height: 22px; }
    .tt-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
    .tt-muted { color: var(--tt-muted); }

    /* ---------- Estrutura ---------- */
    .tt-shell { display: grid; grid-template-columns: 248px minmax(0, 1fr); min-height: 100vh; }
    .tt-side { position: sticky; top: 0; height: 100vh; overflow-y: auto; background: #fff; border-right: 1px solid var(--tt-line); padding: 20px 14px; display: flex; flex-direction: column; gap: 22px; }
    .tt-main { min-width: 0; padding: 32px 40px 48px; display: flex; flex-direction: column; gap: 24px; }
    .tt-top, .tt-tabs { display: none; }

    .tt-brand { display: flex; align-items: center; gap: 12px; padding: 4px 8px; text-decoration: none; }
    .tt-brand-icone { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #9566C9, #653B92); display: grid; place-items: center; color: #fff; flex: none; box-shadow: 0 6px 14px rgba(101, 59, 146, .3); }
    .tt-brand-icone svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .tt-brand-nome { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 21px; letter-spacing: -.02em; }

    .tt-nav { display: flex; flex-direction: column; gap: 4px; }
    .tt-nav a { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 12px; color: #40354D; text-decoration: none; font-weight: 500; }
    .tt-nav a:hover { background: #F1ECF6; color: var(--tt-ink); }
    .tt-nav a.on { background: var(--tt-brand-soft); color: #4B2A72; font-weight: 700; }
    .tt-nav a.on::after { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--tt-lime); margin-left: auto; box-shadow: 0 0 0 3px #EAF6C2; }

    .tt-side-rodape { margin-top: auto; display: flex; flex-direction: column; gap: 10px; }
    .tt-portal { display: flex; align-items: center; gap: 10px; padding: 0 8px; text-decoration: none; }
    .tt-portal img { height: 30px; width: auto; display: block; }
    .tt-portal span { font-size: 12px; color: var(--tt-muted); line-height: 1.3; }
    .tt-portal b { color: var(--tt-ink); }
    .tt-user { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-top: 1px solid var(--tt-line); }
    .tt-user-nome { font-weight: 600; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tt-icbtn { width: 40px; height: 40px; border-radius: 12px; display: inline-grid; place-items: center; background: none; border: 0; color: var(--tt-muted); cursor: pointer; text-decoration: none; }
    .tt-icbtn:hover { background: #F1ECF6; color: var(--tt-ink); }

    /* ---------- Troca de módulos ---------- */
    .tt-modbtn { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid var(--tt-line); background: #fff; font-weight: 600; font-size: 14px; color: var(--tt-ink); cursor: pointer; }
    .tt-modbtn:hover { background: #F9F6FC; }
    .tt-modpanel { position: fixed; z-index: 70; left: 14px; top: 140px; width: 340px; max-height: calc(100vh - 160px); overflow-y: auto; background: #fff; border: 1px solid var(--tt-line); border-radius: 20px; box-shadow: 0 20px 50px rgba(35, 27, 44, .18); padding: 14px; }
    .tt-modtit { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--tt-muted); padding: 2px 8px 8px; display: flex; align-items: center; justify-content: space-between; }
    .tt-modgrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 4px; }
    .tt-modi { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 12px; text-decoration: none; color: var(--tt-ink); min-height: 56px; }
    .tt-modi:hover, .tt-modi.cur { background: var(--tt-brand-soft); }
    .tt-modi img { width: 36px; height: 36px; border-radius: 10px; object-fit: contain; flex: none; background: #fff; }
    .tt-modi b { display: block; font-size: 13.5px; line-height: 1.25; }
    .tt-modi small { display: block; font-size: 12px; color: var(--tt-muted); }
    .tt-modtodos { display: flex; align-items: center; justify-content: center; min-height: 44px; margin-top: 8px; border-top: 1px solid var(--tt-line-2); color: var(--tt-brand-d); font-weight: 700; text-decoration: none; }
    .tt-modfundo { position: fixed; inset: 0; z-index: 65; background: rgba(23, 18, 29, .25); }

    /* ---------- Cabeçalho de página ---------- */
    .tt-ph { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between; }
    .tt-eyebrow { color: var(--tt-muted); font-weight: 500; }
    .tt-h1 { font-size: 34px; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }
    .tt-acoes { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .tt-crumb { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; color: var(--tt-muted); font-weight: 500; }
    .tt-crumb a { text-decoration: none; font-weight: 600; color: var(--tt-muted); }
    .tt-crumb a:hover { color: var(--tt-ink); }
    .tt-crumb b { color: var(--tt-ink); font-weight: 600; }

    /* ---------- Botões, chips, campos ---------- */
    .tt-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 0 16px; border-radius: 12px; border: 1px solid #DAD2E3; background: #fff; color: var(--tt-ink); font: 600 14px 'Hanken Grotesk', system-ui, sans-serif; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .tt-btn:hover { background: #F5F1F9; }
    .tt-btn.pri { background: var(--tt-brand); border-color: var(--tt-brand); color: #fff; }
    .tt-btn.pri:hover { background: var(--tt-brand-d); }
    .tt-btn.ok { background: #15803D; border-color: #15803D; color: #fff; }
    .tt-btn.ok:hover { background: #126B33; }
    .tt-btn.ghost { border-color: transparent; background: none; color: var(--tt-muted); }
    .tt-btn.ghost:hover { background: #F1ECF6; color: var(--tt-ink); }
    .tt-btn.perigo { color: #A4231A; border-color: transparent; background: none; }
    .tt-btn.perigo:hover { background: #FDE7E4; }
    .tt-btn.sm { min-height: 36px; padding: 0 12px; font-size: 13px; }
    .tt-btn.bloco { width: 100%; }

    .tt-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .tt-chip.g { padding: 4px 12px; font-size: 13px; }
    .st-pl { background: #DDF3F1; color: #0F6A63; }
    .st-af { background: #ECE8F1; color: #453C52; }
    .st-an { background: #E1EBFF; color: #1D4ED8; }
    .st-ag { background: #FFEFCC; color: #8A5200; }
    .st-co { background: #DCF4E4; color: #14692F; }
    .chip-ok { background: #DCF4E4; color: #14692F; }
    .chip-aviso { background: #FFEFCC; color: #8A5200; }
    .chip-risco { background: #FDE7E4; color: #A4231A; }

    .tt-prio { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; color: var(--tt-muted); white-space: nowrap; }
    .tt-prio::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .p-urgente::before { background: #B42318; }
    .p-alta::before { background: #C2570C; }
    .p-media::before { background: #1D4ED8; }
    .p-baixa::before { background: #6B7280; }
    .tt-meta { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 500; color: var(--tt-muted); white-space: nowrap; }
    .tt-meta.atraso { color: #A4231A; font-weight: 700; }

    .tt-av { width: 26px; height: 26px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 11px; font-weight: 700; color: #fff; flex: none; border: 2px solid #fff; }
    .tt-av.g { width: 34px; height: 34px; font-size: 12px; }
    .tt-avs { display: inline-flex; padding-left: 6px; }
    .tt-avs .tt-av { margin-left: -6px; }

    .tt-card { background: #fff; border: 1px solid var(--tt-line); border-radius: 20px; box-shadow: var(--tt-shadow); }
    .tt-card.pad { padding: 22px 24px; }
    .tt-sec-tit { margin: 0; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--tt-muted); font-weight: 700; font-family: 'Hanken Grotesk', sans-serif !important; }

    .tt-field { display: flex; flex-direction: column; gap: 6px; }
    .tt-label { font-weight: 700; font-size: 13px; color: #40354D; }
    .tt-input, .tt-select, .tt-textarea { width: 100%; min-height: 44px; padding: 0 14px; border-radius: 12px; border: 1px solid #DAD2E3; background: #fff; font: 500 15px 'Hanken Grotesk', system-ui, sans-serif; color: var(--tt-ink); }
    .tt-textarea { padding: 12px 14px; min-height: 120px; resize: vertical; line-height: 1.6; }
    .tt-input:focus, .tt-select:focus, .tt-textarea:focus { outline: 2px solid #C9A9E4; outline-offset: 1px; border-color: var(--tt-brand); }
    .tt-input.busca { padding-left: 40px; }
    .tt-erro { color: #A4231A; font-size: 12.5px; font-weight: 600; }
    .tt-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .tt-busca { position: relative; display: block; }
    .tt-busca > svg { position: absolute; left: 13px; top: 13px; color: var(--tt-muted); }

    .tt-seg { display: inline-flex; padding: 4px; background: #EDE8F2; border-radius: 14px; gap: 2px; }
    .tt-seg a, .tt-seg button { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 10px; font-weight: 600; color: #40354D; text-decoration: none; min-height: 36px; border: 0; background: none; cursor: pointer; font-size: 14px; }
    .tt-seg .on { background: #fff; color: var(--tt-ink); box-shadow: 0 1px 2px rgba(35, 27, 44, .12); }

    .tt-flt { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 14px; border-radius: 12px; border: 1px solid #DAD2E3; background: #fff; font: 500 14px 'Hanken Grotesk', system-ui, sans-serif; color: var(--tt-ink); cursor: pointer; }
    .tt-flt.on { background: var(--tt-brand-soft); border-color: #DCC9EC; color: #4B2A72; }
    .tt-flt select { border: 0; background: transparent; font: inherit; font-weight: 700; color: inherit; padding: 0 4px; min-height: 40px; cursor: pointer; }

    .tt-alert { padding: 14px 18px; border-radius: 14px; font-weight: 600; }
    .tt-alert.ok { background: #DCF4E4; color: #14692F; }
    .tt-alert.erro { background: #FDE7E4; color: #A4231A; }

    .tt-bar { height: 6px; border-radius: 99px; background: #E9E2F0; overflow: hidden; }
    .tt-bar > i { display: block; height: 100%; border-radius: 99px; background: var(--tt-brand); }
    .tt-vazio { text-align: center; padding: 40px 20px; color: var(--tt-muted); border: 1.5px dashed #CFC4DA; border-radius: 16px; background: #FBF9FD; }

    /* ---------- Projetos ---------- */
    .tt-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .tt-stat { padding: 18px 20px; }
    .tt-stat-rot { color: var(--tt-muted); font-weight: 600; font-size: 13px; }
    .tt-stat-val { font-size: 34px; font-weight: 800; letter-spacing: -.02em; margin-top: 4px; font-family: 'Montserrat', sans-serif; }
    .tt-stat-sub { color: var(--tt-muted); font-size: 13px; }
    .tt-barra-filtros { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
    .tt-projetos { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
    .tt-pc { display: flex; flex-direction: column; gap: 16px; padding: 20px; text-decoration: none; color: var(--tt-ink); transition: box-shadow .15s, border-color .15s; }
    .tt-pc:hover { box-shadow: 0 8px 24px rgba(35, 27, 44, .08); border-color: #D3C8DE; }
    .tt-tile { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 18px; flex: none; font-family: 'Montserrat', sans-serif; }
    .tt-tile.g { width: 56px; height: 56px; border-radius: 16px; font-size: 24px; }
    .tt-clamp2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tt-pc-rodape { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 14px; border-top: 1px solid var(--tt-line-2); }
    .tt-pc-acoes { display: flex; gap: 6px; }

    /* ---------- Kanban ---------- */
    .tt-filtros { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .tt-kanban { display: flex; gap: 14px; align-items: flex-start; overflow-x: auto; padding-bottom: 12px; }
    .tt-col { flex: none; width: 300px; background: #EEE9F3; border-radius: 18px; padding: 10px; display: flex; flex-direction: column; gap: 10px; }
    .tt-col-h { display: flex; align-items: center; gap: 8px; padding: 4px 6px; }
    .tt-col-lista { display: flex; flex-direction: column; gap: 10px; min-height: 60px; }
    .tt-k { background: #fff; border: 1px solid var(--tt-line); border-radius: 14px; padding: 14px; display: flex; flex-direction: column; gap: 10px; cursor: grab; box-shadow: 0 1px 2px rgba(35, 27, 44, .04); }
    .tt-k:hover { box-shadow: 0 6px 18px rgba(35, 27, 44, .10); }
    .tt-k.feita { opacity: .7; }
    .tt-k-tit { font-weight: 600; font-size: 14.5px; line-height: 1.35; text-decoration: none; color: var(--tt-ink); }
    .tt-k-tit:hover { color: var(--tt-brand-d); }
    .tt-k.feita .tt-k-tit { text-decoration: line-through; }
    .tt-k-topo { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .tt-k-base { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .tt-k-proj { font-size: 12px; font-weight: 700; text-decoration: none; }
    .tt-fantasma { background: #E4D6F2; }
    .tt-add { display: flex; align-items: center; gap: 6px; padding: 10px 8px; color: #4A4156; font-weight: 600; text-decoration: none; border-radius: 12px; min-height: 44px; }
    .tt-add:hover { background: #E4DCEC; }

    /* ---------- Lista do projeto ---------- */
    .tt-lista { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
    .tt-lista-fases { flex: 999 1 560px; min-width: 0; display: flex; flex-direction: column; gap: 16px; }
    .tt-lista-lado { flex: 1 1 280px; max-width: 340px; display: flex; flex-direction: column; gap: 16px; }
    .tt-fase-h { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px 18px; cursor: default; }
    .tt-fase-h h2 { font-size: 16px; font-weight: 700; flex: 1 1 200px; }
    .tt-grip { color: #B5ABC1; cursor: grab; flex: none; }
    .tt-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 10px 18px; border-top: 1px solid var(--tt-line-2); min-height: 56px; background: #fff; }
    .tt-row:hover { background: #F9F6FC; }
    .tt-row-tit { flex: 1 1 260px; min-width: 0; font-weight: 600; text-decoration: none; color: var(--tt-ink); }
    .tt-row-tit:hover { color: var(--tt-brand-d); }
    .tt-row.feita .tt-row-tit { color: var(--tt-muted); text-decoration: line-through; }
    .tt-ck { width: 22px; height: 22px; accent-color: var(--tt-brand); flex: none; margin: 0; cursor: pointer; }
    .tt-venc { display: flex; gap: 12px; align-items: center; }
    .tt-data-cx { width: 44px; text-align: center; background: #F1ECF6; border-radius: 10px; padding: 5px 0; flex: none; }
    .tt-data-cx small { display: block; font-size: 11px; font-weight: 700; color: var(--tt-muted); text-transform: uppercase; }
    .tt-data-cx b { font-size: 17px; font-weight: 800; line-height: 1.1; }
    .tt-novafase { display: flex; flex-wrap: wrap; gap: 10px; padding: 16px; }

    /* ---------- Árvore ---------- */
    .tt-arvore-box { padding: 28px 20px; overflow-x: auto; min-height: 60vh; background: #fff; border: 1px solid var(--tt-line); border-radius: 20px; box-shadow: var(--tt-shadow); }
    .tt-arvore, .tt-arvore ul, .tt-arvore li { list-style: none; margin: 0; padding: 0; position: relative; }
    .tt-arvore ul { display: flex; flex-direction: column; padding-left: 50px; }
    .tt-arvore li { margin: 12px 0; display: flex; align-items: center; }
    .tt-arvore li::before { content: ''; position: absolute; top: 50%; left: -50px; width: 45px; height: 0; border-top: 2px solid #D9CCE6; }
    .tt-arvore li::after { content: ''; position: absolute; top: 0; left: -50px; width: 0; height: 100%; border-left: 2px solid #D9CCE6; }
    .tt-arvore li:first-child::after { top: 50%; }
    .tt-arvore li:last-child::after { height: 50%; border-radius: 0 0 0 5px; }
    .tt-arvore li:only-child::after { display: none; }
    .tt-arvore > li::before { border: none !important; }
    .tt-arvore ul:not(:first-child)::before { content: ''; position: absolute; top: 50%; left: -50px; width: 50px; height: 0; border-top: 2px solid #D9CCE6; }
    .tt-no { display: block; padding: 10px 15px; border-radius: 14px; border: 1px solid var(--tt-line); background: #fff; min-width: 160px; position: relative; z-index: 2; text-decoration: none; color: var(--tt-ink); transition: transform .15s, box-shadow .15s; }
    a.tt-no:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(35, 27, 44, .12); }
    .tt-no small { display: block; color: var(--tt-muted); font-size: 12px; font-weight: 500; }
    .tt-no.raiz { background: linear-gradient(135deg, #7B4FA8, #653B92); color: #fff; border: 0; min-width: 210px; font-weight: 800; }
    .tt-no.raiz small { color: #E6D8F3; }
    .tt-no.fase { background: var(--tt-brand-soft); border-color: #DCC9EC; font-weight: 700; }
    .tt-no.tarefa { font-size: 13.5px; font-weight: 600; }
    .tt-no.tarefa.st-co { background: #F1FAF4; border-color: #BFE6CC; color: var(--tt-ink); opacity: .85; }
    .tt-no.tarefa.st-an { background: #F3F7FF; border-color: #C5D8FF; color: var(--tt-ink); }
    .tt-no.tarefa.st-ag { background: #FFFAEE; border-color: #F5DDA4; color: var(--tt-ink); }

    /* ---------- Tarefa ---------- */
    .tt-tarefa { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
    .tt-tarefa-corpo { flex: 999 1 520px; min-width: 0; display: flex; flex-direction: column; gap: 20px; max-width: 880px; }
    .tt-tarefa-lado { flex: 1 1 280px; max-width: 340px; display: flex; flex-direction: column; gap: 16px; }
    .tt-kv { margin: 0; padding: 6px 20px; }
    .tt-kv > div { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--tt-line-2); }
    .tt-kv > div:first-child { border-top: 0; }
    .tt-kv dt { color: var(--tt-muted); font-weight: 500; }
    .tt-kv dd { margin: 0; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; text-align: right; }
    .tt-prosa { font-size: 15.5px; line-height: 1.7; color: #2B2234; overflow-wrap: anywhere; }
    .tt-prosa p { margin: 0 0 12px; }
    .tt-prosa ul, .tt-prosa ol { padding-left: 22px; margin: 0 0 12px; }
    .tt-arquivo { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; padding: 14px 0; border-top: 1px solid var(--tt-line-2); }
    .tt-arquivo-ic { width: 40px; height: 40px; border-radius: 10px; background: var(--tt-brand-soft); color: var(--tt-brand-d); display: grid; place-items: center; flex: none; }
    .tt-upload { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 10px; padding: 18px; border: 1.5px dashed #CFC4DA; border-radius: 14px; background: #FBF9FD; }
    .tt-upload input[type=file] { flex: 1 1 220px; font-size: 14px; }
    .tt-editor .ql-toolbar { border-radius: 12px 12px 0 0; border-color: #DAD2E3; background: #FBF9FD; }
    .tt-editor .ql-container { border-radius: 0 0 12px 12px; border-color: #DAD2E3; font-family: 'Hanken Grotesk', sans-serif; font-size: 15px; }

    /* ---------- Assistente IA ---------- */
    .tt-ia { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
    .tt-ia-corpo { flex: 999 1 520px; min-width: 0; max-width: 820px; display: flex; flex-direction: column; gap: 16px; }
    .tt-ia-lado { flex: 1 1 280px; max-width: 340px; padding: 22px; display: flex; flex-direction: column; gap: 18px; }
    .tt-contexto { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 14px; background: var(--tt-brand-soft); color: #3F2362; }
    .tt-passo { display: flex; gap: 12px; align-items: flex-start; }
    .tt-passo > b { width: 28px; height: 28px; border-radius: 50%; background: var(--tt-brand-soft); color: #4B2A72; font-weight: 800; display: grid; place-items: center; flex: none; }
    .tt-sug { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; min-height: 44px; border-radius: 999px; border: 1px solid #DAD2E3; background: #fff; font: 500 14px 'Hanken Grotesk', sans-serif; color: var(--tt-ink); cursor: pointer; text-align: left; }
    .tt-sug:hover { border-color: #C9A9E4; background: #FAF6FD; }
    .tt-prompt { width: 100%; border: 0; outline: 0; resize: none; font: 500 17px/1.6 'Hanken Grotesk', sans-serif; color: var(--tt-ink); background: transparent; padding: 0; }
    .tt-preview { background: #F2EBF8; border: 1px solid #DCC9EC; border-radius: 14px; padding: 18px 20px; color: #2B2234; line-height: 1.6; }
    .tt-sql { margin: 0; padding: 16px; border-radius: 12px; background: #12141C; color: #8BE0A4; font: 500 12.5px/1.6 'JetBrains Mono', monospace; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }

    /* ---------- Bom dia ---------- */
    .tt-bomdia { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
    .tt-bd { padding: 22px; display: flex; flex-direction: column; gap: 14px; }
    .tt-lista-simples { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .tt-lista-simples li { display: flex; align-items: center; gap: 10px; font-weight: 500; }

    /* ---------- Mobile ---------- */
    .tt-fab { position: fixed; right: 16px; bottom: 92px; width: 56px; height: 56px; border-radius: 18px; background: var(--tt-lime); color: #2B1B3D; display: none; place-items: center; box-shadow: 0 8px 20px rgba(120, 150, 20, .35); text-decoration: none; z-index: 40; }

    @media (max-width: 860px) {
        .tt-shell { grid-template-columns: minmax(0, 1fr); }
        .tt-side { display: none; }
        .tt-top { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: #fff; border-bottom: 1px solid var(--tt-line); position: sticky; top: 0; z-index: 50; }
        .tt-top .tt-brand { padding: 0; }
        .tt-top .tt-brand-icone { width: 36px; height: 36px; border-radius: 11px; }
        .tt-top .tt-brand-nome { font-size: 18px; }
        .tt-top-dir { display: flex; align-items: center; gap: 8px; }
        .tt-top .tt-icbtn { width: 44px; height: 44px; background: var(--tt-brand-soft); color: #4B2A72; }
        .tt-main { padding: 20px 16px 110px; gap: 18px; }
        .tt-h1 { font-size: 28px; }
        .tt-tabs { display: grid; grid-template-columns: repeat(5, 1fr); position: fixed; left: 0; right: 0; bottom: 0; background: #fff; border-top: 1px solid var(--tt-line); padding: 6px 4px calc(10px + env(safe-area-inset-bottom)); z-index: 50; }
        .tt-tabs a { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; min-height: 48px; font-size: 11px; font-weight: 600; color: var(--tt-muted); text-decoration: none; }
        .tt-tabs a.on { color: #4B2A72; }
        .tt-tabs a.on svg { color: var(--tt-brand); }
        .tt-fab { display: grid; }
        .tt-modpanel { inset: 0; left: 0; top: 0; width: auto; max-height: none; border-radius: 0; border: 0; padding: 14px 16px 24px; }
        .tt-modbtn { display: none; }
        .tt-form-grid { grid-template-columns: minmax(0, 1fr); }
        .tt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .tt-stat { padding: 14px; }
        .tt-stat-val { font-size: 28px; }
        .tt-acoes { width: 100%; }
        .tt-acoes .tt-busca { flex: 1 1 100%; width: 100% !important; }
        .tt-projetos, .tt-bomdia { grid-template-columns: minmax(0, 1fr); }
        .tt-col { width: 84vw; max-width: 320px; }
        .tt-lista-lado, .tt-tarefa-lado, .tt-ia-lado { max-width: none; flex-basis: 100%; }
        .tt-row { padding: 10px 14px; }
        .tt-row .tt-venc, .tt-row .tt-hide-m { display: none; }
        .tt-arvore-box { padding: 18px 12px; }
    }
</style>
