# Engenhocopolis Module — Agentic Guidelines

## Purpose
Isometric mechanical-automation puzzle (GDD v1.2). Each level has a known package queue that must reach specific collectors. The player places conveyors and machines on a grid, presses EXECUTAR and watches a deterministic, tick-based simulation. MVP = levels 01–10: straight/curved conveyors, gap separator, diverter.

## Structure
- **Controller:** `Http/Controllers/EngenhocopolisController.php` — only `index` (no DB besides the `PortalApp` row; GDD: local-only, no ranking).
- **Views:** `resources/views/index.blade.php` (whole game: logic, SVG renderer and Alpine component, all inside `@verbatim`) and `components/layout.blade.php` (self-contained CSS, no Tailwind; `<x-pwa-head>`).
- **Migration:** `…create_engenhocopolis_portal_app.php` — private app in group "Jogos", PWA fullscreen/any.
- **Icons:** `public/images/apps/engenhocopolis.png` and `public/images/apps/maskable/engenhocopolis.png`.
- **Routes:** `GET /engenhocopolis` under `auth` + `RegistrarAcesso:Engenhocopolis`.
- **Verifier:** `ferramentas/verificar-fases.mjs` (run with `node`; also run by `tests/Feature/EngenhocopolisTest.php`). Checks every level: reference solution wins, fits the stock, is deterministic, has no red warnings, no piece in it is redundant, and an empty board fails with a message.

## Game logic (pure, between `// ENGENHO-LOGICA-INICIO` / `FIM`)
- Grid directions `DIRS`: 0 = +x, 1 = +y, 2 = −x, 3 = −y. Screen: `sx = (x − y)·40`, `sy = (x + y)·20 − z` (camera is the SVG `viewBox`, never part of the logic).
- A level (`FASES[]`): `largura/altura`, `entrada {x,y,dir}`, `coletores [{id, x, y, tipo: 'final'|'fosso'}]`, `obstaculos`, `fixas` (locked pieces), `pacotes [{largura 1..3 (P/M/G), destino}]` (drawn with the destination collector colour `COR_COLETOR` and symbol `SIMBOLO_COLETOR`; colour is not a rule attribute), `estoque {tipo: n}`, `limites {abertura, grupo}`, `referencia` (known solution, also defines the "economy" medal). `setor` indexes `SETORES` (district names).
- Pieces `{tipo, x, y, dir, lado?, abertura?, primeiro?, grupo?}`; `dir` = output direction. A package must arrive moving in `direcaoDeEntrada(p)` (straight/separator/diverter: from the back; curve: from the side given by `lado` `D`/`E`), otherwise it fails with `conexao`.
- Separator: falls if `largura < abertura` (equal stays). Over a `fosso` it is delivered there; elsewhere it fails (`chao`). An uncovered `fosso` swallows any package; a piece over it covers it.
- Diverter: counts packages; `saidaDoDesviador(p, n)` alternates groups of `grupo` between front and side (`lateral(dir, lado)`), starting with `primeiro`.
- `simular(fase, montagem)` → `{frames, vitoria, entregues, total, falha}`. Packages enter every `ESPACO_PADRAO` (2) ticks; it stops at the first failure (`coletor_errado`, `chao`, `sem_esteira`, `saiu`, `obstaculo`, `conexao`, `colisao`, `preso` after `LIMITE_TICKS`) with a plain-language `mensagem` and an optional `dica`.
- `avisosDeConexao` flags outputs before running: `erro` (red) or `aberto` (amber). Never blocks a test run.

## UI notes
- Renderer writes SVG strings into three layers (`chao`, `cena`, `marcas`); `cena` is depth-sorted by `x + y`. Volumetric items are split by `desenharPartes`/`desenharColetorFinal` into `fundo` (depth `d`) and `frente` (depth `d + 0.7`: separator front post + bar + label, diverter housing when it faces the camera, collector front walls); a package is drawn at `floor(fx) + floor(fy) + 0.5` (the cell holding its centre), so it passes under the arch and drops inside the collector. Entrance: the package is invisible on its spawn tick; while leaving it is cut in 3D at the machine face (`corte` in `desenharPacote`), so only the part already outside is drawn (after the first conveyor when the exit faces the camera, before the machine otherwise); for exits facing the camera the part still inside is drawn too, clipped to the opening (`boca-*` clipPath). Static items are cached in `cacheCena` (reset by `redesenharTudo`); diverters are redrawn per frame to show their next output.
- Input: tap/click = select or place; drag a placed piece = move; drag the background, middle button or Space+drag = pan; wheel/pinch = zoom; long press = inspect. Keyboard is optional (1–4, R, F, Delete, Ctrl+Z, Enter, C, Esc).
- Persistence: `localStorage` key `engenhocopolis:v1` (`versao`, `desbloqueada`, `resultados`, `montagens` per level, `falhas`, `introVista`, `prefs {som, reduzir, velocidade}`). Change the shape → bump the version and migrate in `carregarSalvo`.
- Selected piece: yellow glow (SVG filter `#brilho`) instead of an outline; the inspector is an icon-only toolbar (`ICONES`, labels only in `aria-label`/`title`) with visual params (separator: P/M/G chips, falling ones drop; diverter: start front/side + `×grupo`). The (i) button / key I toggles `infoPeca`, which shows the full text from `INFO_PECA` plus the current configuration. Alpine gotcha: bind booleans with `!!` (`:disabled="undefined"` disables the button).
- Medals (never block progress): concluída, econômica (≤ pieces of `referencia`), "1ª" (won without any failed run on that level).
- Adding a level: append to `FASES` with a `referencia`, then run the verifier.
