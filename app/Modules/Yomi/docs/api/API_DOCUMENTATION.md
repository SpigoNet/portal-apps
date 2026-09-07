# Yomi — API REST de consulta de mangás

Consome metadados de mangás de provedores externos (Jikan/MAL e AniList) com fallback, mantendo o banco local como fonte primária.

**Prefixo:** `/yomi/api/v1`
**Autenticação:** headers `X-User-ID` e `X-Token`
**Formato:** JSON

## Autenticação

Todas as rotas, exceto `/health`, exigem os headers:

```http
X-User-ID: 1
X-Token: md5(email + password_hash)
```

O token permanece válido enquanto a senha do usuário não for alterada.

## Prioridade de provedores

A ordem primário → fallback é definida pela configuração do módulo (área `/yomi/configuracoes`, restrita ao dono), que persiste em `yomi_settings`. Sem valores armazenados, usa `YOMI_PROVIDER_PRIORITY` (separada por vírgula; padrão `jikan,anilist`). Provedores desativados não são consultados. Em qualquer endpoint que consulte provedores, o parâmetro opcional `provider=jikan|anilist` força a preferência naquela chamada (respeitando se o provedor está habilitado).

## Endpoints

### `GET /yomi/api/v1/health`

Healthcheck público — não exige autenticação.

```json
{ "module": "yomi", "api": "v1", "status": "up" }
```

### `GET /yomi/api/v1/mangas/busca`

Busca de mangás nos provedores externos (fallback entre eles).

| Query | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `q` | string | sim | Termo de busca (máx. 120 chars) |
| `provider` | string | não | Força `jikan` ou `anilist` |

### `GET /yomi/api/v1/mangas/populares`

Obras em destaque/populares.

| Query | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `limit` | int | não | 1–25 (padrão `YOMI_DISCOVER_LIMIT`, 12) |
| `provider` | string | não | Força `jikan` ou `anilist` |

### `GET /yomi/api/v1/mangas/recentes`

Obras publicadas recentemente.

| Query | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `limit` | int | não | 1–25 (padrão `YOMI_DISCOVER_LIMIT`, 12) |
| `provider` | string | não | Força `jikan` ou `anilist` |

### `GET /yomi/api/v1/mangas/externo/{provider}/{external_id}`

Busca uma obra por ID externo e **a sincroniza para o banco local** (getOrSync). Obra local entrosada é retornada do cache.

```json
{
  "data": {
    "id": 1,
    "title": { "principal": "Berserk", "original": "ベルセルク" },
    "status": "completo",
    "external_ids": { "jikan": "123", "anilist": "999" },
    "local": true
  },
  "meta": { "provider": "jikan", "external_id": "123", "synced": true }
}
```

### `GET /yomi/api/v1/mangas/{id}`

Detalhes de uma obra local (por ID interno do banco). Se estiver stale, sincroniza.

### `POST /yomi/api/v1/mangas`

Catalogar uma obra externa na estante do usuário + marcar "Pretendo Ler".

Body JSON:

```json
{ "provider": "jikan", "external_id": "123" }
```

Se a obra já existe localmente, apenas marca "Pretendo Ler" e retorna 200 com `meta.created = false`. Caso contrário sincroniza e retorna 201 com `meta.created = true`.

## Formato da resposta

Os endpoints de listagem (busca/populares/recentes) retornam obras externas no formato:

| Campo | Descrição |
|-------|-----------|
| `titles` | `romaji`/`english`/`native` |
| `alternative_titles` | array de títulos alternativos |
| `synopsis` | sinopse (ou null) |
| `cover_url` | URL da capa (ou null) |
| `status` | `publicando`/`completo`/`hiato`/`cancelado`/`nao_publicado`/`desconhecido` |
| `format` | `manga`/`oneshot`/etc. (lowercase) |
| `chapters` / `volumes` | ints (ou null) |
| `start_date` / `end_date` | `YYYY-MM-DD` (ou null) |
| `genres` | array de strings |
| `authors` | array de `{ name, original_name, role }` |
| `score` | média (escala 0–10) ou null |
| `external_ids` | mapa `{ jikan?: string, anilist?: string }` |
| `local` | `false` |

Endpoints que operam sobre o banco local retornam `id` (interno), `titles.principal/original`, `source` (`fonte_original`) e `local = true`.

## Erros

| Status | Descrição |
|--------|-----------|
| 401 | Headers de autenticação ausentes/ inválidos |
| 404 | Obra não encontrada |
| 422 | Parâmetros inválidos (validação/provedor inválido) |
| 502 | Todos os provedores externos falharam (com mensagem) |