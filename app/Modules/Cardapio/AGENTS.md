# Cardapio Module — Agentic Guidelines

> Status: **V1–V4 implemented** (all phases, including the AI assistant): people with optional login, routine, ingredients, restrictions/preferences, stock, recipes, weekly plan with automatic generation, scoring and global optimisation, shopping list, history/feedback, leftovers, agenda, lunchbox, recurring products, learning. See `PLANO.md` for progress. This file is the source of truth for the design. Domain terms are in Portuguese on purpose (project convention); prose is in English.

## Purpose
Home cooking assistant: decides **what the household eats, what to buy and what to use first**. It combines who eats (people + routine), restrictions/preferences, the pantry stock with expiry dates and a recipe base to produce a **weekly menu** and a **shopping list** that only contains the deficit between need and stock.

Core idea: instead of asking "what do we eat today?", the system answers "you have these ingredients, these people are at the table, with these restrictions and tastes — these are the best options for the week and this is what is missing".

Goals: less food waste, fewer needless purchases, varied meals, respect for restrictions, school lunchbox (lancheira) support, manual adjustment of everything the system suggests, and progressive learning from the family's choices.

The product is a **constrained optimisation problem** (find the best set of meals for the week), not a recipes CRUD. The engine is the differentiator.

## Prefix / registration
- **Prefix:** `/cardapio` · **View namespace:** `Cardapio::` · **Middleware:** `auth` + `RegistrarAcesso:Cardapio`.
- Follows the "Adding Modules" checklist of the root `AGENTS.md`; plus an idempotent migration creating the private `PortalApp` (`start_link` `/cardapio`, group "Pessoal", icon in `public/images/apps/`, PWA fields), like `Terminal`.
- Modular SSR (Blade + Alpine, forms with redirect + flash). No Livewire, no REST for the UI.
- Tables are prefixed `cardapio_`; models carry no module prefix.

## People and logins (key rule)
A household has many `Pessoa`; **a login is optional**.

| Pessoa | Tipo | `user_id` | Notes |
|---|---|---|---|
| Gustavo | adulto | set | eats everything |
| Mariana (esposa) | adulto | set | avoids lactose |
| Filho (3 anos) | criança | `null` | school lunchbox |
| Empregada | adulto | `null` | lunch on Mondays |

- `cardapio_pessoas.user_id` is **nullable** (FK `users`, `nullOnDelete`, `unique`: a user is linked to at most one person, hence one house).
- Routine, preferences, restrictions, absences and lunchbox depend **only on `pessoa_id`**, never on `user_id`. People without a login take part in menus, quantities and shopping exactly like the others.
- Access: a user may use a `Casa` only if there is an active `Pessoa` with their `user_id` in it (scope everything by `casa_id` resolved from `auth()->id()` via that link; never trust a `casa_id` from the request).
- Users with a login manage the whole house. Everyone with a login can edit their own preferences/restrictions; people without a login are edited by those with one.
- A person can be linked to a user later (or unlinked) without losing history; deleting the user only nulls `user_id`.
- `tem_login` is derived (`user_id !== null`), not stored.

### Pessoa fields
`nome`, `data_nascimento` (age), `tipo` (`adulto|crianca`), `ativo`, `observacoes`, plus related: habitual meals, restrictions, preferences, favourites, disliked and forbidden foods.

## Domain model (planned)
```
Casa
 ├── Pessoa ── Preferencia / Restricao / Ausencia
 ├── RegraRotina            (dia_semana + tipo_refeicao + pessoa)
 ├── ItemEstoque ── Ingrediente ── CategoriaIngrediente
 ├── Receita ── ReceitaIngrediente ── Ingrediente
 │             └── SubstituicaoIngrediente
 ├── PlanoSemanal ── RefeicaoPlanejada ── (pessoas) ── HistoricoRefeicao
 ├── ListaCompras ── ItemCompra ── Ingrediente
 ├── Evento, Sobra, ProdutoRecorrente
```
Key columns (snake_case):
- `Casa`: `nome`.
- `Pessoa`: `casa_id`, `user_id?`, `nome`, `data_nascimento`, `tipo`, `ativo`.
- `Ingrediente`: `nome`, `categoria_id`, `unidade_base` (g, ml, un).
- `ItemEstoque`: `casa_id`, `ingrediente_id`, `local` (despensa|geladeira|freezer|outro), `quantidade`, `unidade`, `aproximada`, `validade`, `data_entrada`, `marca?`, `lote?`, `quantidade_minima`, `quantidade_ideal`, `observacoes`.
- `Receita`: `nome`, `descricao`, `rendimento` (porções), `tempo_preparo`, `dificuldade`, `modo_preparo`, `categorias`, `refeicoes_compativeis`, `favorita`, `frequencia` (alta|media|baixa).
- `ReceitaIngrediente`: `receita_id`, `ingrediente_id`, `quantidade`, `unidade`, `opcional`.
- `SubstituicaoIngrediente`: `ingrediente_id`, `substituto_id`, `compatibilidade`.
- `Preferencia`: `pessoa_id`, `ingrediente_id|receita_id`, `nivel` (muito gosta +5, gosta +3, neutro 0, não gosta −5, odeia −10).
- `Restricao`: `pessoa_id`, `ingrediente_id`, `tipo` (`alergia|intolerancia|preferencia|objetivo`), `gravidade`. Not everything is "forbidden": allergy = never; intolerance = avoid or substitute; preference = discouraged; objective (less lactose/sugar, more protein/vegetables) = soft goal.
- `RegraRotina`: `casa_id`, `pessoa_id`, `dia_semana`, `tipo_refeicao`, `ativo` (routine is modelled apart from plans so nobody re-registers participants every week).
- `PlanoSemanal`: `casa_id`, `inicio_semana`, `status`. `RefeicaoPlanejada`: `plano_id`, `data`, `tipo_refeicao`, `receita_id`, `qtd_pessoas`, pivot with `pessoa_id`, `justificativa`.
- `ListaCompras`: `casa_id`, `plano_id?`, `status`. `ItemCompra`: `ingrediente_id`, `quantidade_necessaria`, `quantidade_estoque`, `quantidade_comprar`, `unidade`, `comprado`, `manual`.
- `HistoricoRefeicao`: `refeicao_planejada_id`, `consumida_em`, `nota`, `feedback` (gostamos muito|gostamos|normal|não gostamos|não fazer novamente).
- `Evento`: day/meal outside the plan (churrasco, almoço fora, pizza, viagem). `Ausencia`: `pessoa_id`, `inicio`, `fim` (absent people leave the calculation). `Sobra`: stock-like leftover with estimated quantity/expiry.
- Meal types (toggleable per house): café da manhã, lanche da manhã, almoço, lanche da tarde, jantar, ceia, **lancheira**.

## Stock rules
- Internal **base unit** per ingredient (g / ml / un); conversions: 1 kg = 1000 g, 1 L = 1000 ml, 1 dúzia = 12 un. Other units: pacote, lata, caixa, pote, garrafa, sachê, saco, bandeja, vidro, tablete, dente, fatia, maço (all = 1 un). **Household measures** (`UnidadeConversor`): colher de chá 5 ml, de sobremesa 10, de sopa 15, xícara 240, copo 200, pitada 0.5; they are accepted by ingredients in ml **and** in g (approximation 1 ml ≈ 1 g, see `compativel`/`porBase`) and are stored in the base unit. `normalizar` understands plurals/aliases ("colheres de sopa", "cs", "xícaras").
- Quantities may be **approximate** (partial cheese, open milk).
- **Validity status:** `normal`, `atencao`, `urgente`, `vencido`; thresholds configurable. Near-expiry items weigh more in planning.
- Low-stock alert when `quantidade < quantidade_minima`. **Stock never goes negative** (RN11).
- Fast entry is a requirement: few clicks, quick add (name + qty + unit + place + expiry), "add everything bought" from the shopping list, later free-text ("comprei 2 kg de frango e 1 dúzia de ovos").
- Marking a meal as prepared/consumed deducts ingredients from stock; leftovers (`Sobra`) become usable stock.

## Menu generation
Input: week, people, routine, stock, recipes, preferences, restrictions, history, events, absences.

1. Determine meals of the week (routine + events − absences).
2. Determine participants of each meal (lancheira is an independent meal — RN09).
3. Filter recipes: drop allergy conflicts and non-adaptable conflicts; adapt through substitutions when possible (e.g. creme de leite → sem lactose) — preferred over discarding (RN01/RN02).
4. Score each recipe (weights configurable later):
```
score = estoque*30 + validade*20 + preferencia*15 + favorito*15 + variedade*10 + praticidade*10
        − repeticao*15 − compras*10
```
5. Select the **best set for the whole week**, not meal by meal (shared ingredients, e.g. frango assado → arroz de frango → torta; avoid three cheese-heavy dishes in a row; do not repeat the same main ingredient/recipe in consecutive days).
6. Sum ingredients scaled by participants.
7. `necessário − estoque = comprar` (RN03/RN10).
8. Present the proposal: accept or edit.

Extras: every suggestion carries a **justification** ("tem 1,2 kg de frango que vence em 3 dias; favorita da família; atende às restrições"); swap one recipe with ranked alternatives; **partial regeneration** (meal / day / week) recalculating stock, shopping list and leftovers; favourites get a bonus but lose score with recent use; rejections and ratings feed back (negative signal reduces appearance, repeated choice raises preference).

### Implemented engine (F6)
`Services/RecomendacaoService` scores a recipe for a meal (0–100, weights in `PESOS`): stock coverage 30, expiry 20, preferences 15, favourite/frequency 15, variety (main ingredient not repeated within ±2 days) 10, practicality 10, shared ingredients 5, −recent repetition 15 (window 10 days), −share still to buy 10. Allergy/intolerance without a usable substitute (same base unit, not restricted for anyone at the table) drops the recipe (RN01); with one it is adapted (RN02) and stored in `cardapio_refeicoes_planejadas.substituicoes`, which `RefeicaoPlanejada::necessidades()` applies to shopping and consumption. `PlanejadorService::gerar` fills meals chronologically over a *virtual stock* (`ContextoPlanejamento`), so a week reuses shared ingredients; it never changes already-consumed meals, and regeneration never repeats the previous recipe. Any plan change recalculates an open shopping list. The choice is greedy; global optimisation is F8.

### History, feedback and leftovers (F7)
- Marking a meal as done writes a `cardapio_historico_refeicoes` row (snapshot: date, meal type, people, recipe and the quantities really deducted in `consumo`), so history survives deleting the plan or recipe. One row per planned meal (idempotent).
- Feedback (`gostamos_muito`…`nao_fazer_novamente`, optional 1–5 `nota`) is a −1..1 value (`HistoricoRefeicao::valor()`); the engine averages the last 5 per recipe (weight 10). The latest feedback `nao_fazer_novamente` removes the recipe from automatic suggestions and alternatives; a manual choice is still allowed, with a warning. Real frequency (times made, last date, acceptance) is shown on the recipe and in `/cardapio/historico`.
- Leftovers (`/cardapio/sobras`): `SobraService::registrar` creates/reuses an ingredient `Sobra de <nome>` (flag `ingredientes.sobra`) and adds a short-expiry stock lot (default 2 days, fridge). They are ordinary stock: recipes can list them as an ingredient and the expiry component ranks those recipes higher. `cardapio_sobras` is a log; discarding removes the lot.

### Agenda, lunchbox, recurring, learning, optimisation (F8)
- **Agenda** (`AgendaSemana`, `/cardapio/agenda`): events (`fora` = meal not planned; `convidados` = extra people, stored in `refeicoes_planejadas.pessoas_extras` and added to `qtd_pessoas`) and absences (person leaves the calculation in the period). `PlanejadorService::sincronizar` reconciles a week with routine + agenda; creating/removing an event or absence re-syncs the affected weeks and recalculates an open shopping list. Participants edited by hand (`pessoas_manual`) are kept (only absent people are removed); "Sincronizar com a rotina" resets them. Consumed meals never change. Removing an event/absence does not re-add meals by itself (use the sync button).
- **Lunchbox**: `casas.lancheira_itens` (default 3) = main recipe + extras (`cardapio_refeicao_itens`, may be adapted like the main one). `RefeicaoPlanejada::necessidades()` sums main + items, so shopping and consumption include them. Generation never repeats a lunchbox recipe (main or item) on the previous/next day; manual items are set with `definirItens`. Recipes for it are those whose compatible meals include `lancheira`.
- **Recurring products** (`/cardapio/recorrentes`): with an ingredient they add the desired quantity to the need before subtracting stock; free items (toilet paper…) enter once per list, even after bought. `ComprasService::gerarRecorrentes` builds a standalone list. Items can be moved to the next shopping list (becomes a manual item).
- **Learning**: `receitas.escolhas` (manually picked) and `receitas.rejeicoes` (a suggestion replaced by another recipe, or "↻ other" on a single meal; bulk regeneration does not count). Component `tanh((escolhas − 1.5·rejeicoes)/5)`, weight 8.
- **Global optimisation** (`PlanejadorService::otimizar`, also the last step of generating a whole week): hill-climbing over the suggested (`origem = sugerida`) non-lunchbox meals; each meal tries its top 5 alternatives and a swap is kept only if the **total week score** rises by >0.5 (up to 3 passes, 8 s budget). Manual choices stay fixed. `ContextoPlanejamento` is incremental (`consumo`, `retirar`, `disponivel`) so a meal can be re-scored without rebuilding the context.
- Date-only columns are cast `date:Y-m-d` (a plain `date` cast stored a datetime and broke equality lookups such as the weekly plan's `inicio_semana`).

## Shopping list
Generated from the plan: grouped by category (hortifruti, carnes, laticínios, mercearia…), always showing **needed / in stock / buy** (never just "buy 1.5 kg"). Also: recurring products (papel higiênico, café, leite, ovos…), manual items, mark as bought, edit quantity, move to next shopping, and "add bought items to stock".

## Business rules
RN01 incompatible mandatory restriction → never suggested · RN02 compatible substitute → may be suggested using it · RN03 stock reduces need · RN04 near-expiry gets priority · RN05 favourites score higher · RN06 recent use loses score · RN07 avoid repeating recipe/main ingredient · RN08 quantity uses the people present in that meal · RN09 lancheira is an independent meal · RN10 list = deficit only · RN11 no negative stock · RN12 leftovers can be registered and reused.

## Lancheira
Independent meal per child/day: configurable weekdays, number of items, allowed/forbidden foods, desired variety (e.g. fruta + sanduíche + bebida); avoid the same item on consecutive days; can be auto-suggested.

## Screens
Dashboard (today's meals and who eats, ingredients needed, expiring items, missing items, next week pending) · Pessoas (incl. optional link to a user) · Rotina · Estoque (tabs despensa/geladeira/freezer, search, quick add) · Receitas · Planejamento semanal (grid day × meal) · Compras · Histórico/feedback.

## AI layer (F9, implemented)
Page `/cardapio/assistente`; code in `Services/Assistente/`. The model is only a **translator** (rule: AI never changes data):
1. `InterpretadorComandos` sends the sentence plus this house's names (people, ingredients, recipes — never another house's) and today's date; the model must answer a JSON `{mensagem, acoes[]}`. Parsing tolerates markdown fences/extra text; provider errors become a safe message (details only in the log).
2. `ValidadorAcoes` is the barrier: whitelist of action types, strict formats (ISO dates in a sane window, quantities > 0, known units via `UnidadeConversor::normalizar`), names matched against the house's registry (`Nomes::achar`: equality, then whole-word containment; ambiguous → rejected with the options), unit/base compatibility, permissions (children only edit their own profile), HTML stripped, max 12 actions. Output is normalised data (ids, base quantities) — free text from the model never reaches the database. Unknown ingredient in `adicionar_estoque` is allowed but flagged "will be created".
3. Read-only questions (`consultar_estoque`, `sugerir_receitas`) are answered at once by `ConsultasAssistente`; suggestions reuse `RecomendacaoService::ranquear` on a hypothetical, unsaved meal (rotina/agenda participants, restrictions, stock; ingredients the user says they have count as available).
4. State-changing actions (`adicionar_estoque`, `baixar_estoque`, `zerar_estoque`, `adicionar_restricao`, `adicionar_preferencia`, `criar_evento`, `criar_ausencia`, `adicionar_compra`, `gerar_cardapio`, `criar_receita`) are **proposed, never applied**: the validated list is stored in the user's session under a random token; the browser sends back only the token and the ticked ids (never action data), the proposal is consumed before applying (one shot), and `ExecutorAcoes` applies each action in its own transaction through the same services the screens use (`EstoqueService`, `AgendaService`, `PlanejadorService`, `ComprasService`), re-checking house, permissions and that the records still exist. One failing action does not undo the others.
- `criar_receita`: the model drafts name, servings, time, difficulty, compatible meals, ingredients (name + quantity + unit) and method; the validator rejects duplicate names, empty/incomplete ingredient lists and incompatible units, converts quantities to the base unit and flags ingredients that will be created. Applied in one transaction (all or nothing).
- Driver: `ProvedorIA` → `AiProviderService::getTextToTextProvider($user)` + `createTextDriver` (Admin settings). Throttled to 20 requests/min. Tests bind a fake `ProvedorIA` (see `tests/Feature/CardapioAssistenteTest.php`; note Laravel reuses the controller instance inside one test, so swap the fake's driver instead of rebinding).
- "Wishes" like "minha esposa quer evitar lactose" only take effect through an ingredient restriction (the engine blocks by ingredient); a free-text `objetivo` is just recorded.

## AI layer (original design notes)
Natural-language commands ("tenho frango, batata e tomate, o que dá para fazer?", "vou receber meus pais domingo, seremos seis"). **AI never changes data by itself**: it only turns text into structured actions (`ingrediente + quantidade + unidade`, `pessoa + restrição + gravidade`) and explains results; stock, restrictions, shopping and scoring are deterministic services. Reuse `App\Modules\Admin\Services\AiProviderService` (`getTextToTextProvider($user)` → `createTextDriver(...)->generateText($messages)`), as the Pidgey module does.

## Planned structure
```
app/Modules/Cardapio/
  CardapioServiceProvider.php   routes.php   AGENTS.md
  Models/        Casa, Pessoa, Ingrediente, ItemEstoque, Receita, PlanoSemanal, ...
  Services/      EstoqueService, PlanejadorService, ComprasService, RecomendacaoService
  Http/Controllers/
  database/migrations/
  resources/views/ (components/layout.blade.php, index.blade.php, ...)
```
Modular monolith: services own the rules; controllers stay thin.

## Roadmap
- **V1 (MVP):** casa, pessoas (login opcional), rotina, ingredientes/categorias, receitas, estoque CRUD (qty, place, expiry), preferences/restrictions/favourites, manual weekly plan with participants, shopping list with stock deduction.
- **V2:** automatic generation + scoring, ingredient substitution, leftovers, history, ratings, favourites, smart repetition control.
- **V3:** preference learning, weekly global optimisation, waste-oriented planning, recurring products, events, absences, smart lunchbox, consumption forecast.
- **V4:** AI assistant (natural language).
- **Later:** nutrition (not a substitute for a nutritionist), meal/week cost, financial planning, metrics (waste, usage before expiry, savings, favourites, variety), notifications (expiring items, plan pending, missing item for tomorrow), barcode/OCR/voice input.

## Testing notes
Follow `tests/Feature/TerminalTest.php`: migrate only `users`, `packages`, `portal_apps` (+ PWA), `metricas_acessos` and the module's own migrations (the full chain is not sqlite-compatible). Cover: guest redirected to `/login`; a user only sees their own `Casa`; people without `user_id` participate in plans and shopping quantities; linking/unlinking a user keeps history; stock never goes negative; shopping list shows only the deficit.

## Telegram (bot Sanji)
Bot do módulo via Pidgey (ver `app/Modules/Pidgey/AGENTS.md`): `Services/Telegram/SanjiTelegram` (comandos `/hoje /amanha /vencendo /estoque /compras`; texto livre vai ao `AssistenteService`, que só propõe, e o toque em ✅ Aplicar executa, uma vez, só para quem pediu). O resumo do dia é um agendamento diário (07:00) do Pidgey com o conteúdo dinâmico `resumo_cardapio` (`ResumoDiarioProvedor`, registrado em `ProvedoresConteudo`; a persona escreve só a abertura a partir de fatos apurados — cobra quando o cardápio do dia não está definido — e o resumo determinístico completo é anexado abaixo; se a IA falhar, a abertura é uma frase fixa); `AgendamentoResumo::garantir` o cria sozinho ao vincular o Telegram ou ao entrar numa casa, e pode ser editado/desativado em `/pidgey`. A persona `sanji` é criada por migration; falta preencher o `telegram_token` e rodar `pidgey:telegram-webhook sanji`.

## MCP (agentes externos)
Endpoint `POST /cardapio/mcp` (JSON-RPC 2.0 stateless, sem SSE; `GET` → 405). Rotas em `mcp.php` (grupo `api`, `throttle:120,1`), controller `McpController`, ferramentas em `Services/Mcp/FerramentasMcp.php`.
- **Auth:** `X-User-ID` + `X-Token` (mesmo token dos MCPs de Mithril/Yomi: `md5(email.password)`, ver `AutenticarMcp::tokenDe`) ou `Authorization: Bearer <id>:<token>`. A casa vem da `Pessoa` ativa do usuário (403 sem casa); nunca do request.
- **Leitura:** `listar_pessoas`, `consultar_estoque`, `estoque_vencendo`, `estoque_baixo`, `ver_cardapio`, `ver_compras`, `listar_receitas`, `detalhar_receita`, `sugerir_receitas`.
- **Escrita:** as ações do assistente (incluindo `alterar_receita`, que substitui a lista de ingredientes só se enviada), aplicadas na hora (o agente/cliente MCP é quem confirma) mas passando por `ValidadorAcoes` + `ExecutorAcoes`, então valem as mesmas regras (nomes casados com a casa, unidades, estoque nunca negativo, permissões). Falha de validação volta como `isError: true`.
- Testes: `tests/Feature/CardapioMcpTest.php`.
- **Sem headers:** `POST /cardapio/mcp/{userId}/{token}` autentica pela URL. A página `/cardapio/conexao-mcp` (menu “🔌 MCP”) mostra esse endereço com botões de copiar, um prompt de contexto sugerido para o agente, o comando do Claude Code e o JSON de configuração.
