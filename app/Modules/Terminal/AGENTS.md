# Terminal Module — Agentic Guidelines

## Purpose
Casual puzzle shown as an old CRT computer. One passenger queue (`● ■ ▲ ◆ ★ ✚`), N stacked parking slots, more buses than slots (each with its own capacity; a type may have several; single use). A bus only leaves when full. Lose with `QUEUE BLOCKED` (front passenger has no parked bus and there is no free slot); win when the queue is empty.

## Structure
- **Controller:** `Http/Controllers/TerminalController.php` — `index`, `registrar` (POST `/terminal/resultado`, keeps each user's best time per phase) and `ranking` (GET `/terminal/ranking?fase=N`, JSON; the only JSON endpoints, used by the Alpine home screen).
- **Model/DB:** `Models/Resultado` → `terminal_resultados` (`user_id`, `fase` 1-based, `tempo_ms`; unique per user+phase).
- **Views:** `resources/views/index.blade.php` (whole game in Alpine) and `components/layout.blade.php` (CRT CSS, phosphor themes `ph-verde|ambar|branco`, self-hosted VT323 at `public/fonts/vt323-latin.woff2`, `<x-pwa-head>`).
- **Migration:** `database/migrations/…create_terminal_portal_app.php` — creates the private `PortalApp` in group "Jogos" with PWA fields.
- **Icons:** `public/images/apps/terminal.png` and `public/images/apps/maskable/terminal.png`.
- **Routes:** all under `auth` + `RegistrarAcesso:Terminal`.
- **Home screen:** overlay `tela === 'home'` (starts open): `> RUN` (plays the saved level), settings (COR, SOM, REBOOT = back to level 1 after a second tap) and "FUNCIONARIO DO MES" with tabs POR FASE (fastest time in a phase), GERAL (lowest average time per phase) and FASES (most phases cleared, tie → lower total time). In-game `MENU` returns to it. Time = first bus pick → queue empty (`g.t0`/`g.tempo`), sent on `CLEAR`.

## Game logic
- Pure functions between `// TERMINAL-LOGICA-INICIO` / `FIM` in `index.blade.php` (no DOM, loaded by Node in ad-hoc tests): `NIVEL_1` (intro from the design), `PROGRESSAO`, `gerarNivel` (deterministic per level number), `analisar`, `heuristica`, plus the engine used by the UI (`vagaDeEmbarque`, `embarcarTudo`, `situacao`, `escolhas`, `jogar`).
- Rules: a bus is `{t, cap: 2..4}`; a type may have several buses with different capacities; buses of the same (type, cap) are identical. The front passenger boards the parked bus of its type with room (the fullest one; ties → lowest slot). A bus departs when full and frees its slot. Lose (`QUEUE BLOCKED`) when the front passenger has no parked bus with room and either no slot is free or no free bus of its type is left. Win when the queue is empty; buses still parked (not full) then depart anyway (`partirRestantes`) before `CLEAR`.
- The only real decision is **which bus of the front passenger's type to park** (big = slot held until more of that type arrive; small = leaves soon but the type's bus supply runs out). Parking early never helps (only the front boards), so `analisar` only explores that choice.
- `analisar(nivel)` = memoised DFS → `{solucionavel, pAleatorio}` (chance of winning by choosing uniformly at random). `gerarNivel` accepts a shuffled queue only if it is solvable, `pAleatorio <= pMax` of its row in `PROGRESSAO`, and the "always smallest" / "always largest" heuristics fail at least `falhas` times. Max 8 buses per level (two button rows).
- UI: with ≥4 slots the slot height is measured (`medir`) and the bus art switches to the 3-line `ARTE_COMPACTA` and scales down; the queue shows a highlighted first passenger instead of the `^` row when it has more than 12 passengers.
- Animations are `async/await` chains guarded by `g.run`; `reiniciar()` bumps it to cancel pending steps. Timings (ms): key 100 (ENTER 160), bus in ~850, boarding 340/passenger, FULL 550, departure 1000.
- Beware: Alpine's string `:style` replaces the whole inline style; use the object form (`:style="{ gap: ... }"`) when the element also has a static `style`.

- Keyboard: the on-screen keys are functional (`@pointerdown`, not click — the layout's double-tap guard swallows fast taps) and so is the physical keyboard (`teclaFisica`; Tab cycles symbols). Command grammar: `BUS <símbolo><lugares>` + ENTER (`enviarComando` → `estacionar`, same path as tapping a bus button). Row 0 is digits + `DEL`; the `SIMB` key swaps row 0 for the level's symbols.
- Modes: `modo` = `normal` (tap/click; keyboard also works) or `hard` (bus buttons disabled, keyboard only). Chosen on the home screen (`trocarModo`, saved in `localStorage` `terminal:modo`); progress is kept per mode (`terminal:nivel:<modo>`, the old `terminal:nivel` key counts as normal). `terminal_resultados` is unique per `user_id + modo + fase`, and `registrar`/`ranking` take `modo`, so every ranking is separate per mode.

## Extras (ver `IDEIAS.md` para o catálogo completo e o backlog)
- **Desafio diário:** `gerarNivelDiario(HOJE)` (`HOJE` vem do servidor, `Carbon::today()`); mesma fila para todos. `terminal_diarios` (único por user+modo+data, melhor tempo); `GET/POST /terminal/diario`; a sequência (streak) é calculada em `TerminalController::sequencia`. `diario = true` no Alpine: não mexe no progresso das fases e o `CLEAR` volta ao menu.
- **Dica:** `melhorEscolha(nivel, estado)` (pura; reaproveita `criarAvaliador`, a busca memorizada de `analisar`). Custa +10 s (`g.penalidade`, somada ao `g.tempo`) e tira uma estrela. Botão `DICA` no console (no lugar do antigo `COR`, que continua na tela inicial e no comando `COR`) ou comando `HINT`/`DICA`.
- **Estrelas:** `estrelasDaFase(dicas, falhas)`: 3, menos 1 por dica e menos 1 por ter falhado antes (`this.falhas`, zerado ao mudar de fase). Coluna `terminal_resultados.estrelas` (mantém a maior). Tela `FASES` lista 1..progresso (rejogar fase antiga não faz o progresso recuar: `novoJogo` só grava `terminal:nivel:<modo>` se o nível for maior).
- **Conquistas:** catálogo `CONQUISTAS` no cliente; códigos válidos em `TerminalController::CONQUISTAS` (mantenha os dois em sincronia); `terminal_conquistas`; `POST /terminal/conquista` é idempotente. A elegibilidade é a função pura `conquistasElegiveis(ctx)`.
- **Temas e scanlines:** fósforos `verde|ambar|branco|ciano|vermelho|papel` (`.ph-*` no layout); `SCAN` (`off|leve|normal|forte`) vira a variável CSS `--scan`, guardada em `terminal:scan`.
- **Shell falso:** `comandoShell()` roda antes da gramática `BUS` em `enviarComando` (HELP, STATS, WHOAMI, DATE, LS, PING, COR, SOM, SCAN, MENU/LOGOUT, HACK, RM, SUDO…). O limite do comando digitado é 16 caracteres.
- `ranking` agora também devolve `estrelas` (mapa fase→estrelas do usuário no modo) e `conquistas` (códigos do usuário).
- **Telas (`tela`):** `home` (`> RUN`, `DESAFIO DIARIO`, `CONFIGURACOES`, `RANK`), `config` (modo, escolher fase, COR/SOM/SCAN, REBOOT), `rank` (Funcionário do mês + botão de conquistas), `fases`, `conquistas` e `jogo`. Telas cheias usam a classe `.t-scr` (flex) no layout: o `x-show` do Alpine apaga `display` inline, então nunca ponha `display:flex` no `style` de um elemento com `x-show`.
- **Boot de BIOS:** `tela === 'boot'` (inicial, uma vez por sessão via `sessionStorage`; `rodarBoot`/`pularBoot`/`encerrarBoot`; `BOOT: ON/OFF` em Configurações, `terminal:boot`). `teclaFisica` pula o boot com qualquer tecla.
