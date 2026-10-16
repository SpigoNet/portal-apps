# Terminal — Ideias e roadmap

Catálogo de ideias para o módulo. As marcadas com ✅ foram implementadas; as demais ficam como backlog.

| # | Ideia | Estado |
|---|-------|--------|
| 1 | Desafio diário | ✅ |
| 2 | Modo contra o relógio (rush) | backlog |
| 3 | Modo sem desfazer / permadeath | backlog |
| 4 | Mutadores de regras | backlog |
| 5 | Dica (`HINT`) | ✅ |
| 6 | Replay da solução | backlog |
| 7 | Estrelas por fase + seleção de fases | ✅ |
| 8 | Conquistas | ✅ |
| 9 | Ghost do melhor tempo | backlog |
| 10 | Compartilhar resultado | backlog |
| 11 | Mais temas de fósforo + scanlines ajustáveis | ✅ |
| 12 | Comandos de "shell" falsos | ✅ |
| 13 | Boot de BIOS animado | ✅ |
| 14 | Modo daltônico | backlog |
| 15 | Editor/criador de fases | backlog |
| 16 | Offline total (PWA) | backlog |

---

## Implementadas

### 1. Desafio diário
Uma fila igual para todos, gerada por `gerarNivelDiario(data)` (mesmo gerador determinístico das fases, com a data `AAAA-MM-DD` como semente; a dificuldade gira conforme o dia). A data vem do servidor (`hoje`), então todos jogam a mesma fila no mesmo dia.
- Botão `DESAFIO DIARIO` na tela inicial, com a sequência (streak) de dias seguidos.
- Aba `DIARIO` no "Funcionário do mês": ranking do dia (menor tempo), separado por modo.
- Tabela `terminal_diarios` (`user_id`, `modo`, `data`, `tempo_ms`; único por usuário+modo+data, guarda o melhor tempo).
- Endpoints: `GET /terminal/diario?modo=` e `POST /terminal/diario`. O servidor só aceita a data de hoje.
- A sequência conta dias consecutivos com pelo menos um desafio concluído (em qualquer modo); continua válida se hoje ainda não foi jogado mas ontem foi.
- Jogar o diário não altera o progresso das fases.

### 5. Dica (`HINT`)
`melhorEscolha(nivel, estado)` (lógica pura, reaproveita a busca memorizada de `analisar`) devolve o ônibus que maximiza a chance de vitória. Mostra `DICA: BUS ●3` no console e destaca o botão do ônibus.
- Botão `DICA` no console ou comando `HINT` / `DICA`.
- Custo: **+10 s** no tempo da fase por dica, e a fase perde uma estrela (ver 7).
- Se o estado atual já não tem saída, avisa `SEM SAIDA: REBOOT`.

### 7. Estrelas por fase
Estrelas de 1 a 3 por fase: começa em 3, perde 1 se usou dica e perde 1 se falhou (`QUEUE BLOCKED`) antes de concluir (mínimo 1).
- Guardadas no servidor (`terminal_resultados.estrelas`, mantém a melhor), por modo.
- Tela `FASES` (seleção): grade com as fases liberadas (1 até a fase atual) e as estrelas de cada uma; tocar numa fase joga ela.
- A tela de `CLEAR` mostra as estrelas ganhas.

### 8. Conquistas
Catálogo no cliente (nome + descrição) e códigos válidos no servidor (`TerminalController::CONQUISTAS`). Guardadas em `terminal_conquistas` (`user_id`, `codigo`; único). Ao desbloquear aparece `ACHIEVEMENT UNLOCKED: <NOME>`.

| Código | Nome | Como ganhar |
|--------|------|-------------|
| `BOOT` | BOOT SEQUENCE | Concluir a primeira fase |
| `ZERO_WASTE` | ZERO WASTE | 3 estrelas numa fase |
| `PLENO` | FUNCIONARIO PADRAO | 3 estrelas em 5 fases |
| `MARATONISTA` | HORA EXTRA | Concluir 10 fases |
| `TECLADISTA` | DATILOGRAFO | Concluir uma fase no modo HARD |
| `PONTUAL` | PONTO BATIDO | Concluir o desafio diário |
| `HABITUE` | EXPEDIENTE FIXO | 3 dias seguidos no diário |
| `VETERANO` | CRACHA DOURADO | 7 dias seguidos no diário |
| `HACKER` | ACESSO ROOT | (secreta) tentar `SUDO` no console |

Tela `CONQUISTAS` na tela inicial (a secreta aparece como `???` até ser ganha).

### 11. Temas de fósforo e scanlines
- Novos fósforos: `ciano`, `vermelho` e `papel` (papel térmico, tela clara). Ficam em `components/layout.blade.php` (`.ph-*`).
- `SCAN` alterna a intensidade das scanlines (`OFF` / `LEVE` / `NORMAL` / `FORTE`), guardada em `localStorage` (`terminal:scan`) e aplicada pela variável CSS `--scan`.

### 12. Comandos de "shell" falsos
Reconhecidos em `comandoShell()` (antes da gramática `BUS`): `HELP`, `HINT`/`DICA`, `STATS`, `WHOAMI`, `DATE`, `LS`, `PING`, `COR`, `SOM`, `MENU`/`LOGOUT`/`EXIT`, `REBOOT` (refaz o boot de BIOS e volta à tela inicial), `HACK`, `RM` e `SUDO …` (sempre `PERMISSION DENIED`; libera a conquista secreta).

### 13. Boot de BIOS animado
Ao abrir o jogo aparece uma tela de BIOS (versão, contagem de memória até 640K, checagem de teclado/monitor/garagem/fila, `USUARIO: <nome>`) e depois a tela inicial. Roda uma vez por sessão do navegador (`sessionStorage terminal:boot`); qualquer toque ou tecla pula; `prefers-reduced-motion` não mostra. `BOOT: ON/OFF` em Configurações (`localStorage terminal:boot`).

---

## Backlog

### 2. Modo contra o relógio (rush)
Tempo corre; cada fase resolvida soma segundos. Vale a pontuação total, em ranking próprio.

### 3. Modo sem desfazer / permadeath
Uma tentativa por fase, sem `REBOOT`. Um terceiro `modo` ao lado de `normal` e `hard` (rankings já são separados por modo).

### 4. Mutadores de regras
Vaga travada (só certos tipos), ônibus VIP (embarca fora da frente), ônibus que parte sozinho após N passageiros, passageiro coringa. Cada regra muda o que `analisar` explora.

### 6. Replay da solução
Ao ganhar, mostrar a solução ótima ou o caminho do jogador em câmera rápida, usando a mesma cadeia de animações.

### 9. Ghost do melhor tempo
Linha de comparação com o ritmo da melhor run (própria ou do primeiro colocado).

### 10. Compartilhar resultado
Texto para colar no WhatsApp: `TERMINAL #fase 12.4s ●■▲`.

### 14. Modo daltônico
Troca de símbolos e rótulos extras.

### 15. Editor/criador de fases
Usuário monta fila e ônibus, `analisar` valida; compartilhamento por código.

### 16. Offline total (PWA)
Service worker para jogar sem rede e sincronizar resultados depois.
