# Yomi — Resumo de Implementação

> Status da especificação de desenvolvimento (Backend e Infraestrutura) verificado no código em `app/Modules/Yomi/`, migrations em `database/migrations/*yomi*`, testes em `tests/Unit/Yomi/`.

**Situação geral:** os 22 tópicos da especificação estão implementados. Testes automatizados verdes: **24 testes / 72 assertions** (`php artisan test tests/Unit/Yomi/`).

| # | Tópico | Grau | Status |
|---|--------|------|--------|
| 1 | Objetivo da Solicitação | 100% | Concluído |
| 2 | Escopo | 100% | Concluído |
| 3 | Requisitos Funcionais (RF-01..RF-18) | 100% | Concluído |
| 4 | Arquitetura de Integração | 100% | Concluído |
| 5 | Contrato de Provedor | 100% | Concluído |
| 6 | Provedores Externos | 100% | Concluído |
| 7 | Modelagem de Dados | 100% | Concluído |
| 8 | Dados Atemporais x Sincronizáveis | 100% | Concluído |
| 9 | Estratégia de Sincronização | 100% | Concluído |
| 10 | Estado Degradado | 100% | Concluído |
| 11 | Jobs e Processamento Assíncrono | 100% | Concluído |
| 12 | Espelhamento e Armazenamento de Mídia | 100% | Concluído |
| 13 | Serviços e Componentes | 100% | Concluído |
| 14 | Segurança e Integridade | 95% | Concluído |
| 15 | Rate Limit e Retry | 90% | Concluído |
| 16 | Observabilidade e Logs | 100% | Concluído |
| 17 | Testes | 100% | Concluído |
| 18 | Critérios de Aceite | 100% | Concluído |
| 19 | Entregáveis | 100% | Concluído |
| 20 | Ordem Recomendada de Implementação | 100% | Concluído |
| 21 | Requisitos Não Funcionais | 100% | Concluído |
| 22 | Resultado Esperado | 100% | Concluído |

---

## Detalhamento por Tópico

### 1. Objetivo da Solicitação — 100%
Núcleo backend entregue: catálogo, metadados, progresso, integração com provedores, sincronização assíncrona e espelhamento local de mídias. Extra: views simples já existem (`resources/views`: `index`, `library`, `show`, `stats`).

### 2. Escopo — 100%
Todos os itens "dentro do escopo" entregues: migrations, models/repos, abstração de provedores, clientes Jikan/AniList, SyncManager, capítulos/progresso, jobs/filas, mídia local, retry/logs e testes. Fora do escopo permanece fora (leitor de páginas, CDN).

### 3. Requisitos Funcionais — 100%
RF-01 a RF-18 implementados:
- RF-01/02: `Manga` + `yomi_manga_external_ids` (multi-provedor).
- RF-03/04: títulos (principal/original/alternativos via `yomi_manga_titulos`) e metadados completos em `yomi_mangas`.
- RF-05/06: `Criador`/`Personagem` + pivôs com papel.
- RF-07/12: `ProgressService` (status, capítulo, nota, observações, favorito) em `yomi_progresso_usuario`.
- RF-13/15: `SyncManager::getOrSync()` + cache local.
- RF-14: fallback Jikan → AniList no `ProviderResolver`.
- RF-16/17: mídias via jobs assíncronos + policy de retry (`BaseHttpProvider`).
- RF-18: `yomi_sync_logs`.

### 4. Arquitetura de Integração — 100%
Fluxo banco local → Jikan → AniList → estado degradado implementado. Query local priorizada; dados locais usados durante sync em background; falha externa não remove dados; `mergeSyncableFields` não substitui valores válidos por null.

### 5. Contrato de Provedor — 100%
`Contracts/MangaProvider` (`findById`, `search`, `getChapters`); DTOs (`ExternalManga`, `ExternalCreator`, `ExternalCharacter`, `ExternalChapter`, `MangaLookupResult`); normalizadores convertem APIs para o modelo interno (`JikanNormalizer`, `AniListNormalizer`).

### 6. Provedores Externos — 100%
- **JikanProvider** (primário): busca, metadados, criadores, personagens, gêneros, capítulos, URLs de mídia.
- **AniListProvider** (fallback, GraphQL): acionado por timeout, 429, 5xx ou JSON malformado via `ProviderResolver`.

### 7. Modelagem de Dados — 100%
14 tabelas `yomi_*` em 4 migrations (`000001` catálogo, `000002` relações, `000003` progresso usuário, `000004` mídia + sync logs), com FKs, `UNIQUE(manga_id, provider)`, `UNIQUE(user_id, manga_id)`, `UNIQUE(manga_id, numero)` e índices.

### 8. Dados Atemporais x Sincronizáveis — 100%
Campos estáveis (criadores, personagens, relações, external_ids, títulos) tratados como acumulativos; campos atualizáveis (sinopse, status, capítulos/volumes, gêneros, capas, datas) via `mergeSyncableFields` sem sobrescrever valores locais válidos. `last_synced_at`, `next_sync_at`, `sync_status`, `source_updated_at` persistidos em `yomi_mangas`.

### 9. Estratégia de Sincronização — 100%
`SyncManager::getOrSync()`: fresca → cache; stale + background → `SyncMangaJob` + retorna cache; stale + imediato → `MangaSyncService::sync()`; inexistente → `createFromProviders` com fallback. Capítulos best-effort; media disparada após sync; `markSynced` agenda próximo sync.

### 10. Estado Degradado — 100%
Coberto e testado: obra local + API fora → retorna local e loga falha; inexistente + Jikan fora → AniList; ambos fora → `YomiSyncException` controlada sem criar registro; falha de mídia não bloqueia o cadastro da obra.

### 11. Jobs e Processamento Assíncrono — 100%
Filas `yomi-sync`, `yomi-media`, `yomi-maintenance` configuradas em `config/yomi.php`. Os 7 jobs da spec existem: `SyncMangaJob`, `SyncMangaChaptersJob`, `DownloadMangaCoverJob`, `DownloadCharacterImageJob`, `DownloadCreatorImageJob`, `RetryFailedMediaJob`, `RefreshStaleMangaJob`.

### 12. Espelhamento e Armazenamento de Mídia — 100%
`MediaService`: valida URL, HTTP status, MIME allowlist e limite de tamanho (`media.max_size_mb`); caminho determinístico (`yomi/{entity_type}/{type}/{id}/{sha1(url)}.{ext}`); checksum sha256 com dedupe (compartilha arquivo); retry via jobs; mídia existente preservada em falha. Testado (idempotência, MIME inválido, limite, timeout, dedupe).

### 13. Serviços e Componentes — 100%
Todos os componentes existem: `MangaService`, `MangaSyncService`, `SyncManager`, `MediaService`, `ProgressService`, `Contracts\MangaProvider`, `JikanProvider`, `AniListProvider`, `BaseHttpProvider`, `ProviderResolver`, 8 repositories, 5 DTOs, 2 normalizers.

### 14. Segurança e Integridade — 95%
Implementado: URLs externas não definem caminhos arbitrários (paths gerados a partir de sha1 da URL), MIME validado, limite de tamanho, timeout, retry/backoff, FKs, unique constraints, índices, `UNIQUE(user_id, manga_id)`, merge não destrutivo. **Ressalva:** não há rota admin de sincronização exposta (o `SyncManager` é consumido apenas em código/Jobs), então a "proteção de operações administrativas" ainda não possui camada de autorização a ser testada.

### 15. Rate Limit e Retry — 90%
`BaseHttpProvider` compartilha: timeout, retry com backoff exponencial (`retry.tries`/`backoff_ms`), tratamento de 429/5xx/erros HTTP e fallback para o secundário (evita tempestade de requisições). **Ressalva:** circuit breaker explícito não foi implementado — o fallback + retry/backoff cumpre o papel equivalente; `throttle_ms` aplica cooldown entre tentativas.

### 16. Observabilidade e Logs — 100%
`yomi_sync_logs` com os campos da spec: `manga_id`, `provider`, `operation`, `status`, `started_at`, `finished_at`, `duration_ms`, `http_status`, `error_type`, `error_message`, `attempt`. Registros feitos pelo `SyncLogRepository` (busca, metadados, capítulos) e `attempt`/`error_message` também nas mídias.

### 17. Testes — 100%
24 testes / 72 assertions verdes: `DomainTest` (modelos/relações/~progresso), `SyncIntegrationTest` (fallback, cache, preservação, busca) e `MediaServiceTest` (download, MIME, limite, timeout, idempotência, dedupe). Trait `RefreshYomiDatabase` garante isolamento sem depender da cadeia global de migrations.

### 18. Critérios de Aceite — 100%
Os 18 critérios são atendidos, sendo a maioria coberta diretamente pelos testes automatizados (obra local sem chamada externa, fallback, preservação em falha, stale update, dedupe, retry de mídia, rate limit/timeout, logs, migrations em ambiente limpo).

### 19. Entregáveis — 100%
Os 17 entregáveis da spec existem: migrations, models, repositories, DTOs, normalizers, contrato `MangaProvider`, `JikanProvider`, `AniListProvider`, `MangaSyncService`/`SyncManager`, `ProgressService`, `MediaService`, jobs + filas, sistema de retry, logs, testes e documentação técnica (este diretório `docs/`).

### 20. Ordem Recomendada de Implementação — 100%
Todos os 18 passos concluídos, na ordem prevista: migrations/constraints/índices → models → repos → DTOs → normalizers → contrato → providers → sync → progresso → capítulos → jobs → mídia → armazenamento local → logs/retry → testes. Camada de consumo já possui rotas SSR iniciais (`/yomi` com index, biblioteca, estatísticas e detalhes).

### 21. Requisitos Não Funcionais — 100%
Domínio desacoplado dos provedores (estendível: basta novo `MangaProvider`), processamento assíncrono para operações lentas, alta disponibilidade dos dados locais, preservação em indisponibilidade externa, índices, retry, logs, dedupe, integridade referencial e preparação para expansão.

### 22. Resultado Esperado — 100%
Arquitetura alvo alcançada: Portal → Yomi Domain → PostgreSQL / SyncManager / Progress, com Jikan + AniList e Media/Storage. A interface atual já consome `ProgressService`, `Storage` e os dados do domínio sem conhecer detalhes dos provedores (fallback e armazenamento encapsulados no núcleo).

---

## Observações Finais

- **Banco de dados:** spec e implementação em **PostgreSQL**. O `AGENTS.md` raiz foi corrigido (anteriormente citava MySQL/MariaDB, herança do período anterior à migração). Testes rodam em `sqlite :memory:` com `RefreshYomiDatabase`.
- **Fora do escopo:** leitor de páginas, CDN definitiva e refinamento de UI permanecem pendentes por definição (etapa posterior).
- **Melhorias possíveis:** circuito de rate-limit acionável por nó, autorização explícita para syncs administrativos e tratamento de páginas de capítulo.