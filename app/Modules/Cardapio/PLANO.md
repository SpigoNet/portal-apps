# Plano de implementação: módulo Cardapio

## Context
A documentação do módulo já existe em `app/Modules/Cardapio/AGENTS.md` (spec adaptada da especificação original). Agora é preciso **implementá-lo**, seguindo o roteiro V1 → V4 da spec. O Gustavo pediu um plano salvo com **progresso atualizado ao longo da execução**.

**Acompanhamento:** o primeiro passo da execução é copiar este plano para `app/Modules/Cardapio/PLANO.md` (versionado no repo). A cada etapa concluída, marco `[x]` nesse arquivo (e neste), com data e nota curta se algo mudou.

## Fontes de consulta (já no módulo)
- `app/Modules/Cardapio/AGENTS.md` — spec adaptada ao padrão do projeto (manda em caso de conflito).
- `app/Modules/Cardapio/Especificação do Sistema — Assistente de Planejamento Alimentar Residencial.md` — spec original completa (seções 1–75), consultada em cada fase para detalhes (ex.: §20 fórmula de score, §47 algoritmo, §53–55 telas de estoque, §23–27 compras).

## Padrões a reaproveitar (já mapeados)
- **Provider/registro:** copiar `app/Modules/Terminal/TerminalServiceProvider.php` (`loadViewsFrom`, `loadMigrationsFrom`, `Route::middleware(['web'])->group(routes.php)`); registrar em `bootstrap/providers.php`.
- **Rotas:** `Terminal/routes.php` — `Route::prefix('cardapio')->name('cardapio.')->middleware(['auth', RegistrarAcesso::class.':Cardapio'])`.
- **PortalApp:** migration idempotente como `Terminal/database/migrations/…create_terminal_portal_app.php` (`firstOrCreate` por `start_link`, grupo "Pessoal", PWA). Ícones em `public/images/apps/cardapio.png` e `public/images/apps/maskable/cardapio.png`; `<x-pwa-head start-link="/cardapio"/>`; cores PWA seguem `2026_10_12_160000_configurar_pwa_dos_apps.php`.
- **Layout:** `Terminal|QuebraCabeca/resources/views/components/layout.blade.php` (viewport, Alpine, Vite). Alpine para interatividade; formulários com redirect + flash.
- **IA (fase final):** `Admin/Services/AiProviderService` (`getTextToTextProvider($user)`, `createTextDriver()->generateText`), como `Pidgey/Services/InterpretadorPersonaService`.
- **Testes:** padrão `tests/Feature/TerminalTest.php` (migrar só users, packages, portal_apps, wpa, metricas_acessos + migrations do módulo; `actingAs(User::factory()->create())`).
- **Lint/tipos:** `./vendor/bin/pint`; tipagem estrita, imports ordenados (AGENTS.md raiz).

## Decisões de arquitetura
- Acesso por `Pessoa.user_id` (nullable). Um `CasaAtual` (service/helper) resolve a `Casa` do `auth()->id()` via pessoa ativa; controllers nunca aceitam `casa_id` do request. Global scope `casa_id` nos models da casa.
- Unidade base por ingrediente (g/ml/un) e `UnidadeConversor` (kg→g, L→ml, dúzia→12) usado por estoque, receitas e compras.
- Regras em `Services/` (Estoque, Planejador, Compras, Recomendacao); controllers finos.
- Estoque nunca negativo (RN11), validado no `EstoqueService`.

## Fases e progresso
Legenda: `[ ]` pendente · `[~]` em andamento · `[x]` concluído

### F0 — Fundação do módulo ✅ (2026-10-14)
- [x] Copiar este plano para `app/Modules/Cardapio/PLANO.md`
- [x] `CardapioServiceProvider` + `routes.php` + registro em `bootstrap/providers.php`
- [x] Layout `Cardapio::layout` (nav: Início, Pessoas, Rotina, Estoque, Receitas, Planejamento, Compras) + `<x-pwa-head>`
- [x] Migration do `PortalApp` (grupo Pessoal, PWA) + ajuste de cor em migration/lista PWA; ícones
- [x] `CasaAtual` + `Casa` model; tela de criação da casa no primeiro acesso (cria a `Pessoa` do usuário logado)
- [x] Teste: visitante → `/login`; PortalApp criado privado
- [x] Seção do módulo já está no `AGENTS.md` raiz (ok); status "implementado" atualizado no marco V1 (F5)

### F1 — Pessoas, rotina, refeições (V1) ✅ (2026-10-14)
- [x] Migrations `cardapio_casas`, `cardapio_pessoas` (`user_id` nullable, `nullOnDelete`, único por casa quando preenchido), `cardapio_regras_rotina`, tipos de refeição ativos por casa
- [x] CRUD de Pessoa com vínculo/desvínculo opcional a usuário (select de usuários livres)
- [x] Tela de rotina: grade dia × refeição × pessoas (Alpine), `RegraRotina`
- [x] Config dos tipos de refeição (incl. lancheira)
- [x] Testes: pessoa sem login participa da rotina; vincular/desvincular mantém histórico; isolamento entre casas

### F2 — Ingredientes, categorias, restrições e preferências (V1) ✅ (2026-10-14)
- [x] Migrations `cardapio_categorias_ingrediente`, `cardapio_ingredientes`, `cardapio_restricoes`, `cardapio_preferencias`
- [x] CRUD ingredientes/categorias (seed de categorias básicas na migration)
- [x] Restrições (alergia/intolerância/preferência/objetivo) e preferências com peso (+5…−10) por pessoa; favoritos/não gosta
- [x] `UnidadeConversor` + testes unitários
- [x] Teste: login só edita o próprio perfil; adulto com login edita os demais

### F3 — Estoque (V1) ✅ (2026-10-14)
- [x] Migration `cardapio_itens_estoque` (local, qtd, unidade, aproximada, validade, mín/ideal, marca, lote)
- [x] `EstoqueService` (adicionar/baixar/ajustar, conversão, bloqueio de negativo) + status de validade (normal/atenção/urgente/vencido)
- [x] Tela de estoque: abas despensa/geladeira/freezer, busca, entrada rápida, alertas de validade e estoque baixo
- [x] Testes: estoque nunca negativo; classificação de validade

### F4 — Receitas (V1) ✅ (2026-10-14)
- [x] Migrations `cardapio_receitas`, `cardapio_receita_ingredientes`, `cardapio_substituicoes_ingrediente`
- [x] CRUD de receita (ingredientes dinâmicos em Alpine, porções, tempo, dificuldade, refeições compatíveis, favorita, frequência)
- [x] Substituições e opcionais
- [x] Teste: receita escala por nº de pessoas

### F5 — Planejamento semanal manual + Compras (V1) ✅ (2026-10-14)
- [x] Migrations `cardapio_planos_semanais`, `cardapio_refeicoes_planejadas` (+ pivot de pessoas), `cardapio_listas_compras`, `cardapio_itens_compra`
- [x] Criar semana: gera grade pela rotina (participantes por refeição), escolha manual de receita
- [x] `ComprasService`: necessário − estoque = comprar, agrupado por categoria, mostrando necessário/estoque/comprar
- [x] Lista manual, marcar comprado, "adicionar compras ao estoque"
- [x] Marcar refeição como feita → baixa ingredientes do estoque
- [x] Dashboard (hoje, alertas, itens a comprar, próxima semana pendente)
- [x] Testes: lista só com déficit; comprado → entra no estoque; consumo baixa estoque
- [x] **Marco V1 concluído:** rodar `pint` + `php artisan test`; atualizar `AGENTS.md` (status)

### F6 — Geração automática e pontuação (V2) ✅ (2026-10-14)
- [x] `RecomendacaoService`: filtro por restrições (RN01), adaptação por substituição (RN02), score (estoque, validade, preferência, favorito, variedade, praticidade, −repetição, −compras)
- [x] `PlanejadorService`: gerar semana (busca gulosa com bônus por ingredientes compartilhados), justificativa por refeição
- [x] Trocar receita (alternativas ranqueadas), regenerar refeição/dia/semana com recálculo de compras
- [x] Testes: alergia nunca sugerida; lactose adapta; receita recente perde pontos; justificativa presente

### F7 — Histórico, feedback, sobras (V2) ✅ (2026-10-14)
- [x] `cardapio_historico_refeicoes` (nota, feedback), `cardapio_sobras`
- [x] Feedback pós-refeição; frequência; sobras viram estoque com validade curta e entram no score

### F8 — Eventos, ausências, lancheira, recorrentes, aprendizado (V3) ✅ (2026-10-14)
- [x] `cardapio_eventos`, `cardapio_ausencias`, `cardapio_produtos_recorrentes`
- [x] Planejador ignora refeições com evento externo e pessoas ausentes
- [x] Lancheira: itens por dia, sem repetir em dias seguidos
- [x] Aprendizado: ajusta preferência por rejeições/escolhas repetidas
- [x] Otimização semanal global (melhoria sobre a gulosa da F6)

### F9 — IA em linguagem natural (V4) ✅ (2026-10-14)
- [x] `InterpretadorComandosService` via `AiProviderService`: texto → ações estruturadas validadas (estoque, restrição, evento)
- [x] Confirmação do usuário antes de aplicar; aplicação só pelos services determinísticos
- [x] Testes com driver fake

## Notas de execução
- F0/F1: `tests/Feature/CardapioTest.php` (10 testes verdes). Ícones gerados com PIL (`public/images/apps/cardapio.png` + maskable).
- Desvio: o escopo por casa é feito pelo middleware `ResolverCasa` + relações `$casa->pessoas()` (sem global scope), mais simples e seguro.
- Desvio: `cardapio_pessoas.user_id` é `unique` global (um usuário só pertence a uma pessoa/casa), não por casa.
- Tipos de refeição ativos ficam em `cardapio_casas.refeicoes_ativas` (JSON), não em tabela própria.
- F2: conversor com testes unitários; categorias semeadas na migration; ingredientes por casa (nome único); restrições/preferências com permissão (adulto edita todos, criança com login só o próprio).
- F3: quantidades guardadas na unidade base; lotes com mesma validade/local/marca somam; baixa por validade (FEFO), lote zerado é removido; `baixar(..., limitar: true)` consome só o que existir (usado no consumo de refeições).
- F4: receita guarda quantidades na unidade base para o rendimento; `Receita::necessidades(porcoes)` escala e ignora opcionais; substituições são por ingrediente (casa inteira), editadas na tela do ingrediente.
- F5 (marco V1): planos nascem da rotina (um por semana, idempotente); compras guardam necessário/estoque/comprar e só listam déficit; recalcular preserva itens manuais/comprados e desconta o que foi comprado e ainda não entrou no estoque; marcar refeição como feita baixa o estoque (só o que existir) e é idempotente. Validado no navegador (desktop e mobile) com SQLite temporário; precisa de `npm run build` para as classes Tailwind novas.
- F6: `RecomendacaoService::avaliar` = score 0–100 (estoque 30, validade 20, preferência 15, favorito 15, variedade 10, praticidade 10, compartilhado 5, −repetição 15, −compras 10) com justificativa e avisos; alergia/intolerância sem substituto exclui a receita (RN01), com substituto de mesma unidade base adapta (RN02) e a adaptação fica em `refeicoes_planejadas.substituicoes`, usada por compras e consumo. Geração gulosa em ordem cronológica sobre um estoque virtual (`ContextoPlanejamento`), por isso o frango da semana é reaproveitado; refazer refeição/dia/semana não repete a receita anterior e nunca toca refeições já feitas. Lista de compras aberta é recalculada após qualquer mudança. Limitação: a escolha é gulosa; a otimização global da semana é da F8.
- F7: histórico gravado no consumo (retrato + quantidades realmente baixadas); feedback pesa 10 pts na pontuação e `nao_fazer_novamente` bloqueia a receita nas sugestões (decide o feedback mais recente); frequência real na receita e em /historico; sobras = ingrediente `Sobra de …` com lote de validade curta, sem lógica especial no motor (o componente de validade já as prioriza). Nova migration: rodar `php artisan migrate`.
- F8: agenda (eventos fora/convidados e ausências) aplicada na criação e re-sincronização da semana, com preservação de ajustes manuais; lancheira com itens extras (`cardapio_refeicao_itens`) e regra de não repetir em dias seguidos; recorrentes entram no déficit; 'mover para a próxima compra'; aprendizado por escolhas/rejeições; otimização global por subida de encosta sobre a pontuação da semana (limite de 8 s). Bugs achados e corrigidos no caminho: (1) `inicio_semana` era gravado como datetime e a busca por data falhava (cast `date:Y-m-d` em todos os modelos; o teste antigo mascarava a exceção do segundo POST); (2) `diasParaVencer` agora ignora a hora; (3) campo opcional ausente no formulário de evento. Desempenho: semana de 21 refeições × 50 receitas caiu de ~18 s para ~3,5 s com o contexto incremental. Nova migration: rodar `php artisan migrate`.
- F9: assistente em /cardapio/assistente. A IA só traduz texto em JSON; `ValidadorAcoes` filtra (whitelist, formatos, nomes da casa, unidades, permissões, sem HTML); consultas respondem na hora pelo motor; ações que alteram dados ficam numa proposta na sessão (token aleatório, uso único) e só são aplicadas após confirmação, pelos mesmos serviços das telas (`ExecutorAcoes`). Extraí `AgendaService` (compartilhado com o controller) e `EstoqueService::zerar`. 28 testes com driver de IA falso, incl. tentativas de injeção/forjar dados. Sem migration nova.
- Classes Tailwind novas só aparecem em produção após `npm run build` (em dev, `composer dev` resolve).

## Verificação (a cada fase)
- `./vendor/bin/pint --test` e `php artisan test tests/Feature/Cardapio*` verdes
- Conferir no navegador (`composer dev` + preview) o fluxo da fase, incluindo mobile
- Marcar a fase em `app/Modules/Cardapio/PLANO.md` e relatar o que foi feito/mudou

## Pendências conhecidas
- Migrations ainda **não rodadas** no banco de desenvolvimento (rodar `php artisan migrate`).
- A otimização é uma subida de encosta (ótimo local), não busca exaustiva; troca uma refeição por vez e não mexe na lancheira.
- Remover evento/ausência não recoloca refeições sozinho (botão "Sincronizar com a rotina").

## Estado final
F0–F9 concluídas. Backlog fora do plano original: entrada por voz/foto de nota fiscal, nutrição e custos (spec §68–70), notificações (§56), métricas (§67).

## Ordem sugerida de entrega
F0 → F1 → F2 → F3 → F4 → F5 (V1 utilizável) e só então pausar para revisão do Gustavo antes de F6+.
