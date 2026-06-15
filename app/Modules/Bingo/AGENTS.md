# Bingo Module — Agentic Guidelines

## Purpose
Multiplayer bingo game with themed sprite sheets. Public access (no auth required to play). Supports QRCode joining, real-time polling, and child-friendly visuals.

## Structure
- **Controller:** `Http/Controllers/BingoController.php` — Full CRUD + game logic.
- **Models:** `Models/BingoPartida`, `BingoJogador`, `BingoCartela`.
- **Views:** `resources/views/` — `index`, `create`, `jogo` (main game SPA via Alpine.js), `historico` (auth-only).
- **Layout:** `resources/views/components/layout.blade.php` — Standalone child-friendly layout (no auth nav).

## Routes (prefix: `/bingo`, kebab-case names)
| Method | URI | Name | Auth |
|--------|-----|------|------|
| GET | `/` | `bingo.index` | No |
| GET | `/criar` | `bingo.create` | No |
| POST | `/criar` | `bingo.store` | No |
| GET | `/historico` | `bingo.historico` | Yes |
| GET | `/temas/{tema}` | `bingo.temas` | No (with .png constraint) |
| GET | `/{codigo}` | `bingo.show` | No |
| POST | `/{codigo}/entrar` | `bingo.join` | No |
| POST | `/{codigo}/trocar-cartela` | `bingo.trocar-cartela` | No |
| POST | `/{codigo}/iniciar` | `bingo.iniciar` | No |
| POST | `/{codigo}/sortear` | `bingo.sortear` | No |
| POST | `/{codigo}/marcar` | `bingo.marcar` | No |
| POST | `/{codigo}/declarar-bingo` | `bingo.declarar-bingo` | No |
| GET | `/{codigo}/estado` | `bingo.estado` | No |

## Game Flow
1. **Create match** → choose theme from `temas/*.png`, optionally play along
2. **Lobby** → host sees QRCode, players join by scanning or visiting URL
3. **Card swap** → players can regenerate their 3×3 card while in lobby
4. **Start** → host locks all cards, game begins
5. **Draw** → host draws numbers (1-25 pool), displayed above all players' cards
6. **Mark** → players manually tap cells (no auto-mark)
7. **Bingo** → first complete row/column/diagonal (3 in a line) wins

## Real-time
- Polling-based (2s interval) via `GET /{codigo}/estado`
- Token-based identification (stored in localStorage)
- Sound effects via Web Audio API
- Confetti animation on bingo win

## Themes
PNG files in `app/Modules/Bingo/temas/` — must be 5×5 sprite sheets (25 cells). Served via `bingo.temas` route.

## Mobile
- Fully responsive (mobile-first). Cards adapt from 60px cells on mobile to 80px+ on desktop.
- Touch-optimized with `active:scale-95` feedback.
- Layout stacks vertically on small screens.
