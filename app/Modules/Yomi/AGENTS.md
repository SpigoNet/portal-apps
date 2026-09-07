# Módulo Yomi — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **Yomi** gerencia o catálogo pessoal de **mangás** do usuário: obras, capítulos, progresso de leitura e mídias espelhadas localmente. Os metadados são sincronizados a partir de provedores externos:

- **Jikan** (MyAnimeList) — provedor primário.
- **AniList** — provedor fallback (GraphQL).

O banco local é a **fonte operacional primária**: os provedores são contato de sincronização, não a fonte de verdade da UI.

---

## 2. Estrutura de Diretórios

```
app/Modules/Yomi/
├── Contracts/
│   └── MangaProvider.php              # Interface: findById, search, getChapters
├── DTOs/
│   ├── ExternalManga.php              # Obra vinda de provedor (chapters = int; chapterList = array)
│   ├── ExternalCreator.php
│   ├── ExternalCharacter.php
│   ├── ExternalChapter.php
│   └── MangaLookupResult.php          # Resultado de SyncManager::getOrSync
├── Models/                            # Models Eloquent do domínio (pasta do módulo)
├── Enums/                             # ProviderName, StatusLeitura, StatusPublicacao, etc.
├── Exceptions/                        # YomiSyncException, ProviderUnavailableException, etc.
├── Providers/
│   ├── BaseHttpProvider.php           # Retry/backoff/throttle compartilhados
│   ├── JikanProvider.php
│   ├── AniListProvider.php
│   └── ProviderResolver.php           # Prioridade configurável: Jikan → AniList (padrão)
├── Http/
│   ├── Controllers/
│   │   ├── YomiController.php         # Web (SSR)
│   │   └── Api/MangaApiController.php # API REST (`/yomi/api/v1`)
│   └── Middleware/
│       └── TokenAuth.php              # Auth por headers (padrão TreeTask)
├── Normalizers/                       # JikanNormalizer, AniListNormalizer
├── Repositories/                      # Acesso a dados (Manga, Capitulo, Midia, SyncLog, etc.)
├── Services/
│   ├── MangaService.php               # createFromExternal / updateFromExternal + relações
│   ├── MangaSyncService.php           # sync() de metadados de uma obra existente
│   ├── SyncManager.php                # Ponto de entrada: getOrSync() / search() / discover() / recent()
│   ├── ProgressService.php            # Status, nota, favorito, observações, capítulos lidos
│   ├── MediaService.php               # Espelhamento de mídias (capas, personagens, criadores)
│   └── YomiSettingsService.php        # Config dinâmica: prioridade, provedores ativos, base_url
├── Jobs/                              # Filas yomi-sync / yomi-media / yomi-maintenance
├── Http/
│   ├── Controllers/
│   │   ├── YomiController.php         # Web (SSR)
│   │   └── YomiSettingsController.php # Web — configurações do módulo (dono)
│   └── Middleware/
│       ├── TokenAuth.php              # Auth por headers (padrão TreeTask)
│       └── EnsureYomiOwner.php        # Garante acesso somente ao dono (id=1)
├── api.php                            # Rotas da API REST (TokenAuth)
├── YomiServiceProvider.php
└── routes.php
```

As **migrations ficam em `database/migrations/2026_09_06_00000*.php`** (globais, convenção do Mithril), não dentro do módulo.

---

## 3. Rotas

**Prefixo:** `/yomi`  
**Nome base:** `yomi.*`  
**Middleware:** `web`, `auth`, `RegistrarAcesso:Yomi`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/yomi` | `yomi.index` | Dashboard de leitura |
| GET | `/yomi/biblioteca` | `yomi.library` | Biblioteca por status |
| GET | `/yomi/estatisticas` | `yomi.stats` | Estatísticas da jornada |
| GET | `/yomi/descobrir` | `yomi.discover` | Explorar: destaques (`SyncManager::discover`) ou busca (`?q=`) |
| GET | `/yomi/descobrir/{provider}/{externalId}` | `yomi.discover.external` | Detalhes de obra externa (sem persistir) |
| POST | `/yomi/descobrir` | `yomi.discover.register` | Adiciona obra externa à estante (`provider` + `external_id`) |
| GET | `/yomi/mangas/{manga}` | `yomi.mangas.show` | Detalhes da obra |
| POST | `/yomi/mangas/{manga}/proximo-capitulo` | `yomi.mangas.mark-next` | Marca próximo capítulo lido |
| POST | `/yomi/mangas/{manga}/remover` | `yomi.mangas.remove` | Remove a obra da estante do usuário (remove do catálogo se for o último dono) |
| GET | `/yomi/status` | `yomi.status` | Healthcheck do módulo (status sincronização) |
| GET | `/yomi/configuracoes` | `yomi.settings` | Configurações do módulo — **somente dono** (`EnsureYomiOwner`) |
| POST | `/yomi/configuracoes` | `yomi.settings.update` | Persiste provedores/prioridade — **somente dono** |

> **UI já implementada** em `app/Modules/Yomi/resources/views` (namespace `Yomi::`) com layout em `components/layout.blade.php`. A seção **Descobrir** (`discover.blade.php`) consome `SyncManager::discover()`/`search()` e não depende diretamente dos provedores.

> **Configurações do módulo** (`settings.blade.php`): área restrita ao usuário com `id === yomi.owner_user_id` (default `1`). O link só aparece na sidebar para o dono; qualquer outro usuário recebe `404` ao acessar a rota. Permite ativar/inativar provedores, editar a `base_url` de cada API e reordenar a prioridade.

---

## 3.1 API REST (consulta de mangás)

**Prefixo:** `/yomi/api/v1`  
**Middleware:** `App\Modules\Yomi\Http\Middleware\TokenAuth` (exceto `/health`)

Autenticação por headers (mesmo padrão do TreeTask):

```http
X-User-ID: 1
X-Token: md5(email + password_hash)
```

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/yomi/api/v1/health` | `yomi.api.health` | Healthcheck público |
| GET | `/yomi/api/v1/mangas/busca?q=&provider=&limit=` | `yomi.api.mangas.busca` | Busca nos provedores (fallback) |
| GET | `/yomi/api/v1/mangas/populares?limit=&provider=` | `yomi.api.mangas.populares` | Destaques/populares |
| GET | `/yomi/api/v1/mangas/recentes?limit=&provider=` | `yomi.api.mangas.recentes` | Lançamentos recentes |
| GET | `/yomi/api/v1/mangas/externo/{provider}/{external_id}` | `yomi.api.mangas.externo` | Busca externa por id + sincroniza (getOrSync) |
| GET | `/yomi/api/v1/mangas/{id}` | `yomi.api.mangas.show` | Detalhes da obra local (sync se stale) |
| POST | `/yomi/api/v1/mangas` | `yomi.api.mangas.store` | Catalogar obra externa + marcar "Pretendo Ler" |

Parâmetro opcional `provider=jikan|anilist` força a preferência em qualquer chamada; sem ele, usa `yomi.provider_priority`. Falha em todos os provedores → HTTP 502. Detalhes da API: `app/Modules/Yomi/docs/api/API_DOCUMENTATION.md`.

---

## 4. Tabelas do Banco de Dados

Prefixo: `yomi_`

| Tabela | Propósito |
|--------|-----------|
| `yomi_mangas` | Obra central do catálogo |
| `yomi_manga_external_ids` | ID por provedor (`provider`, `external_id`), UNIQUE `(manga_id, provider)` |
| `yomi_manga_titulos` | Títulos alternativos/sinônimos |
| `yomi_generos`, `yomi_manga_generos` | Gêneros normalizados |
| `yomi_criadores`, `yomi_manga_criadores` | Autores/ilustradores com papel |
| `yomi_personagens`, `yomi_manga_personagens` | Personagens com papel |
| `yomi_capitulos` | Capítulos conhecidos (UNIQUE `manga_id, numero`) |
| `yomi_progresso_usuario` | Progresso por usuário/obra (UNIQUE `user_id, manga_id`) |
| `yomi_usuario_capitulos` | Capítulos individualmente concluídos (UNIQUE `user_id, capitulo_id`) |
| `yomi_midias` | Mídias espelhadas (checksum **indexado**, não único — dedupe compartilha checksum) |
| `yomi_sync_logs` | Histórico de sincronização (metadados/busca/capítulos) |
| `yomi_settings` | Configurações dinâmicas (`key` único + `value` JSON) — prioridade de provedores e overrides por provedor |

---

## 5. Fluxo de Sincronização

```
SyncManager::getOrSync(mangaId | externalId+provider)
→ tenta achar obra local (por manga_id ou external_id)
   ├─ fresca (< stale_after_minutes) → retorna cache sem chamar provedor
   ├─ stale + dispatchInBackground → enfileira SyncMangaJob e retorna cache
   ├─ stale + sync imediato → MangaSyncService::sync() → updateFromExternal
   └─ inexistente → createFromProviders → MangaService::createFromExternal
```

- **Prioridade configurável e dinâmica:** a ordem primário → fallback vem de `yomi_settings` (`provider_priority`), com fallback para `yomi.provider_priority` (env `YOMI_PROVIDER_PRIORITY`, default `jikan,anilist`). `YomiSettingsService::priority()` sempre inclui provedores habilitados não listados e **nunca** retorna provedores desativados. `SyncManager::search()/discover()/recent()` aceitam `?preferred=ProviderName` para forçar um provedor na chamada (respeitando se está habilitado).
- **Ativar/desativar provedores:** `yomi_settings.providers.*.enabled` controla quais APIs são consultadas. `BaseHttpProvider` resolve `base_url`/timeout/retry via `YomiSettingsService::providerConfig()` (merge do armazenado com o `config/yomi.php`).
- **Fallback:** timeout/429/5xx/JSON malformado no primário aciona o próximo provedor habilitado.
- **Não destrutivo:** falha total do sync marca `sync_status = falha`, registra `yomi_sync_logs`, mas **não remove dados locais**. O título (`titulo`) local é autoritativo e nunca é sobrescrito pelo provedor (`mergeSyncableFields`).
- **Capítulos são best-effort:** falha na listagem de capítulos não interrompe o sync de metadados.
- **Media após sync:** `MediaService::enqueueMediaFor()` dispara jobs nas filas `yomi-media`/`yomi-sync`.

---

## 6. Configuração

`config/yomi.php` (carregado automaticamente por estar em `config/`):

| Chave | Descrição |
|-------|-----------|
| `owner_user_id` | ID do dono com acesso às configurações (env `YOMI_OWNER_USER_ID`, default 1) |
| `provider_priority` | Ordem primário → fallback padrão (env `YOMI_PROVIDER_PRIORITY`, default `jikan,anilist`) — sobrescrito por `yomi_settings` |
| `providers.jikan/anilist` | `base_url` padrão, `timeout`, `throttle_ms`, `retry.tries/backoff_ms` — `base_url`/atividades podem ser sobrescritos via `yomi_settings` |
| `discover.limit` | Quantidade padrão de destaques (env `YOMI_DISCOVER_LIMIT`, default 12) |
| `sync.stale_after_minutes` | Idade para considerar obra stale (default 1440) |
| `sync.next_sync_after_minutes` | Janela até o próximo sync (default 1440) |
| `media.disk` | Disco de storage (default `local`) |
| `media.max_size_mb`, `allowed_mime_types`, `timeout` | Limites de download |
| `queues` | Filas: `yomi-sync`, `yomi-media`, `yomi-maintenance` |

---

## 7. Testes

**Diretório:** `tests/Unit/Yomi/` e `tests/Feature/Yomi/`

- `DomainTest.php` — cadastro de obras/relações, progresso do usuário.
- `SyncIntegrationTest.php` — fallback (timeout/429), obra local/cache, preservação em falha, busca, prioridade configurável, `discover()`/`recent()`.
- `MediaServiceTest.php` — download, MIME inválido, limite de tamanho, idempotência, dedupe por checksum.
- `MangaApiTest.php` — endpoints da API REST (`/yomi/api/v1`) com `TokenAuth` e `Http::fake`.
- `SettingsServiceTest.php` — prioridade/ativação/merge de config do `YomiSettingsService`.
- `SettingsPageTest.php` — área de configurações restrita ao dono (404 para demais), persistência e efeito no fallback.
- `Concerns/RefreshYomiDatabase.php` — trait obrigatória para testes.

> **IMPORTANTE:** Os testes **NÃO** usam `RefreshDatabase`. A cadeia completa de migrations do portal tem trechos específicos de outros SGBDs que quebram em `sqlite :memory:`. Use `use RefreshYomiDatabase;` e chame `$this->refreshYomiDatabase()` no `setUp()` — isso migra apenas `users` + `yomi_*`.

Para testes de sync, chame `Queue::fake()` no `setUp()` para impedir que os jobs de mídia executem inline (cobririam URLs externas de capa com HTTP real).

---

## 8. Convenções

- **Nomes em português** para entidades de negócio (`Manga`, `Capitulo`, `ProgressoUsuario`, `Criador`); **inglês** para conceitos técnicos (`Provider`, `Normalizer`, `Repository`, `Job`).
- Migrations globais em `database/migrations/` — **não** crie `app/Modules/Yomi/database/migrations`.
- Provedores implementam `Contracts\MangaProvider`; erros de provedor estendem `ProviderUnavailableException` (com `httpStatus`/`errorType`).
- Não use REST API para UI (SSR + Blade conforme regras do portal).

---

## 9. Notas para Agentes de IA

- O `YomiServiceProvider` já está registrado em `bootstrap/providers.php` e binda `MangaProvider::class` → `JikanProvider`.
- `ExternalManga::$chapters` é o **número inteiro** de capítulos; a lista de capítulos fica em `$chapterList`.
- `midias.checksum` é **index** (não único) de propósito — duas mídias idênticas compartilham o mesmo arquivo e checksum.
- Ao tocar migrations Yomi, mantenha os arquivos com prefixo `2026_09_06_00000x_` para não colidir com a ordem global; atualize `RefreshYomiDatabase::yomiMigrationPaths()` se adicionar novas migrations.
- A API REST usa **TokenAuth (X-User-ID + X-Token)** — não `auth:sanctum` (Sanctum não está instalado). O mesmo padrão é duplicado nos módulos Mithril/TreeTask.
- `MangaProvider` agora expõe `topManga()` e `getRecent()` (ambos com `limit`, fallback Jikan↔AniList via `SyncManager`).
- **Configurações dinâmicas:** `YomiSettingsService` (singleton) lê `yomi_settings`; `ProviderResolver` injeta o serviço e `priority()` só retorna provedores habilitados. `BaseHttpProvider::providerConfig()` resolve `base_url` armazenada com fallback no config.
- **RegistrarAcesso** é aplicado apenas nas rotas web, não na API.