# APIs gratuitas de metadados de Mangás

Documento de referência para futura implementação de integração com APIs públicas de mangás/anime.

> **Escopo:** somente metadados de mangás.  
> **Não inclui:** páginas de capítulos, imagens de leitura, download de scans ou conteúdo protegido.

---

## 1. Resumo

APIs avaliadas para fornecer metadados de mangás:

| API | Protocolo | Autenticação | Gratuita | Dados de Mangá | Recomendação |
|---|---|---|---|---|---|
| **AniList** | GraphQL | Não, para consultas públicas | Sim | Excelente | ⭐⭐⭐⭐⭐ |
| **Jikan** | REST/JSON | Não | Sim | Excelente | ⭐⭐⭐⭐⭐ |
| **Kitsu** | REST/JSON:API | Não, para consultas públicas | Sim | Boa | ⭐⭐⭐⭐ |
| **MyAnimeList API** | REST/JSON | OAuth 2 | Sim, com limitações | Excelente | ⭐⭐⭐ |
| **MangaDex API** | REST/JSON | Não, para diversas consultas públicas | Sim | Excelente | ⭐⭐⭐⭐ |

### Estratégia recomendada

Usar **AniList como fonte primária** de metadados e **Jikan como fonte secundária/fallback**.

MangaDex pode ser adicionada posteriormente caso seja necessário complementar informações específicas.

---

# 2. AniList API

## Informações gerais

- **Tipo:** GraphQL
- **Formato:** JSON
- **Autenticação:** não necessária para consultas públicas
- **Endpoint:** `https://graphql.anilist.co`
- **Documentação:** https://docs.anilist.co/
- **Website:** https://anilist.co/

## Principais vantagens

- Grande catálogo de mangás.
- GraphQL permite selecionar exatamente os campos necessários.
- Excelente quantidade de informações.
- Informações sobre autores.
- Gêneros e tags.
- Relações entre obras.
- Informações de publicação.
- Rankings e popularidade.
- Capas.
- Títulos em diferentes idiomas.
- Informações relacionadas a anime.

## Principais entidades

```text
Media
 ├── id
 ├── title
 ├── description
 ├── coverImage
 ├── bannerImage
 ├── type
 ├── format
 ├── status
 ├── chapters
 ├── volumes
 ├── startDate
 ├── endDate
 ├── genres
 ├── tags
 ├── averageScore
 ├── popularity
 ├── favourites
 ├── countryOfOrigin
 ├── source
 ├── staff
 ├── characters
 ├── relations
 └── recommendations
```

## Consulta básica

```graphql
query {
  Page(perPage: 20) {
    media(
      type: MANGA
      sort: POPULARITY_DESC
    ) {
      id

      title {
        romaji
        english
        native
      }

      description

      coverImage {
        large
        medium
      }

      format
      status

      chapters
      volumes

      startDate {
        year
        month
        day
      }

      endDate {
        year
        month
        day
      }

      genres

      averageScore
      popularity

      countryOfOrigin
    }
  }
}
```

## Pesquisa por título

```graphql
query {
  Page(perPage: 10) {
    media(
      type: MANGA
      search: "Berserk"
    ) {
      id

      title {
        romaji
        english
        native
      }

      description

      coverImage {
        large
      }

      chapters
      volumes
      status
      genres
    }
  }
}
```

## Campos recomendados para armazenamento

```text
anilist_id
title_romaji
title_english
title_native
description
cover_url
format
status
chapters
volumes
start_date
end_date
genres
average_score
popularity
country_of_origin
```

## Pontos de atenção

- É GraphQL, não REST.
- É necessário implementar o cliente GraphQL.
- Deve-se respeitar os limites de requisição.
- A descrição pode conter HTML.
- Nem todos os campos estarão preenchidos para todas as obras.

---

# 3. Jikan API

## Informações gerais

- **Tipo:** REST
- **Formato:** JSON
- **Autenticação:** não necessária
- **Versão:** v4
- **Endpoint:** `https://api.jikan.moe/v4`
- **Documentação:** https://docs.jikan.moe/
- **Website:** https://jikan.moe/

## Principais vantagens

- REST simples.
- JSON.
- Não exige autenticação para consultas comuns.
- Excelente para aplicações backend.
- Grande quantidade de informações.
- Dados derivados do MyAnimeList.

## Pesquisa de mangás

```http
GET https://api.jikan.moe/v4/manga?q=berserk
```

## Consulta por ID

```http
GET https://api.jikan.moe/v4/manga/{id}
```

## Campos relevantes

A estrutura retornada possui informações como:

```text
mal_id
url
images
title
title_english
title_japanese
titles
type
chapters
volumes
status
publishing
published
authors
serializations
genres
themes
demographics
synopsis
background
score
scored_by
rank
popularity
members
favorites
```

## Exemplo simplificado

```json
{
  "mal_id": 2,
  "title": "Berserk",
  "title_english": "Berserk",
  "title_japanese": "ベルセルク",
  "type": "Manga",
  "chapters": 376,
  "volumes": 42,
  "status": "Publishing",
  "publishing": true,
  "genres": [
    {
      "mal_id": 1,
      "name": "Action"
    },
    {
      "mal_id": 8,
      "name": "Drama"
    }
  ],
  "score": 9.47,
  "rank": 1,
  "popularity": 2
}
```

## Campos recomendados

```text
mal_id
title
title_english
title_japanese
synopsis
cover_url
type
chapters
volumes
status
publishing
published_from
published_to
authors
genres
themes
demographics
score
rank
popularity
favorites
```

## Pontos de atenção

- É uma API não oficial do MyAnimeList.
- Os dados dependem da disponibilidade no MAL.
- Deve-se respeitar os limites de requisição.
- Pode ser utilizada como fallback para AniList.

---

# 4. Kitsu API

## Informações gerais

- **Tipo:** REST / JSON:API
- **Formato:** JSON
- **Autenticação:** não necessária para consultas públicas
- **Endpoint:** `https://kitsu.io/api/edge`
- **Documentação:** https://kitsu.docs.apiary.io/
- **Website:** https://kitsu.io/

## Pesquisa

```http
GET https://kitsu.io/api/edge/manga?filter[text]=Berserk
```

## Estrutura

A API utiliza JSON:API.

Exemplo conceitual:

```json
{
  "data": [
    {
      "id": "12345",
      "type": "manga",
      "attributes": {
        "canonicalTitle": "Berserk",
        "titles": {
          "en": "Berserk",
          "en_jp": "Berserk"
        },
        "synopsis": "...",
        "averageRating": "89.50",
        "status": "current",
        "chapterCount": 376,
        "volumeCount": 42,
        "startDate": "1989-08-25",
        "endDate": null
      }
    }
  ]
}
```

## Campos relevantes

```text
id
canonicalTitle
titles
abbreviatedTitles
synopsis
averageRating
userCount
favoritesCount
status
chapterCount
volumeCount
startDate
endDate
ageRating
ageRatingGuide
subtype
posterImage
coverImage
```

## Pontos de atenção

- Estrutura JSON:API.
- Pode ser uma boa terceira fonte de dados.
- Menor prioridade que AniList/Jikan para a primeira implementação.

---

# 5. MyAnimeList API

## Informações gerais

- **Tipo:** REST
- **Formato:** JSON
- **Autenticação:** OAuth 2
- **Website:** https://myanimelist.net/
- **Documentação:** https://myanimelist.net/apiconfig/references/api/v2

## Endpoint conceitual

```http
GET https://api.myanimelist.net/v2/manga
```

## Pesquisa

```http
GET https://api.myanimelist.net/v2/manga?q=Berserk
```

## Campos

```text
id
title
main_picture
alternative_titles
start_date
end_date
synopsis
mean
rank
popularity
num_list_users
num_scored_by
nsfw
genres
authors
serializations
media_type
status
num_volumes
num_chapters
```

## Pontos de atenção

A API oficial exige configuração de aplicação e autenticação OAuth.

Por isso, para uma aplicação simples que somente precisa consultar metadados, **Jikan pode ser mais simples de integrar**.

A API oficial passa a ser mais interessante quando houver necessidade de integração direta com funcionalidades do MyAnimeList.

---

# 6. MangaDex API

## Informações gerais

- **Tipo:** REST
- **Formato:** JSON
- **Endpoint:** `https://api.mangadex.org`
- **Documentação:** https://api.mangadex.org/docs/

## Pesquisa

```http
GET https://api.mangadex.org/manga?title=Berserk
```

## Informações disponíveis

```text
id
title
altTitles
description
status
year
contentRating
lastVolume
lastChapter
publicationDemographic
tags
authors
artists
links
originalLanguage
availableTranslatedLanguages
```

## Exemplo conceitual

```json
{
  "id": "manga-id",
  "attributes": {
    "title": {
      "en": "Berserk"
    },
    "description": {
      "en": "..."
    },
    "status": "ongoing",
    "year": 1989,
    "lastVolume": "42",
    "lastChapter": "376",
    "originalLanguage": "ja"
  }
}
```

## Pontos de atenção

A MangaDex é especialmente interessante caso posteriormente seja necessário relacionar o catálogo a capítulos existentes na plataforma.

Para um sistema que **somente precisa de metadados**, entretanto, AniList/Jikan são mais interessantes como fontes principais.

---

# 7. Modelo de dados recomendado

Para evitar acoplamento com uma API específica, o sistema deve possuir um modelo interno próprio.

## Manga

```typescript
interface Manga {
  id: string;

  titles: {
    romaji?: string;
    english?: string;
    native?: string;
    japanese?: string;
  };

  synopsis?: string;

  coverUrl?: string;

  status?: MangaStatus;

  format?: MangaFormat;

  chapters?: number;

  volumes?: number;

  startDate?: string;

  endDate?: string;

  genres: Genre[];

  authors: Author[];

  score?: number;

  popularity?: number;

  source: MangaSource;

  externalIds: {
    anilist?: number;
    mal?: number;
    kitsu?: string;
    mangadex?: string;
  };

  createdAt: string;

  updatedAt: string;
}
```

---

# 8. Enumerações

## Status

```typescript
enum MangaStatus {
  FINISHED = "finished",
  RELEASING = "releasing",
  NOT_YET_RELEASED = "not_yet_released",
  CANCELLED = "cancelled",
  HIATUS = "hiatus",
  UNKNOWN = "unknown"
}
```

## Formato

```typescript
enum MangaFormat {
  MANGA = "manga",
  ONE_SHOT = "one_shot",
  MANHWA = "manhwa",
  MANHUA = "manhua",
  NOVEL = "novel",
  UNKNOWN = "unknown"
}
```

## Fonte

```typescript
enum MangaSource {
  ANILIST = "anilist",
  JIKAN = "jikan",
  KITSU = "kitsu",
  MAL = "mal",
  MANGADEX = "mangadex"
}
```

---

# 9. Arquitetura recomendada

Não é recomendado que o frontend consulte diretamente todas as APIs.

Utilizar uma camada própria:

```text
                    ┌─────────────────────┐
                    │      Frontend       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │   Manga API própria│
                    │      /api/manga     │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Manga Repository   │
                    └──────────┬──────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
              ▼                ▼                ▼
        ┌──────────┐     ┌──────────┐     ┌──────────┐
        │ AniList  │     │  Jikan   │     │  Kitsu   │
        └──────────┘     └──────────┘     └──────────┘
```

---

# 10. Estratégia de sincronização

A aplicação pode manter seu próprio banco de dados.

Exemplo:

```text
API externa
    │
    ▼
Busca / atualização
    │
    ▼
Normalização
    │
    ▼
Banco de dados próprio
    │
    ▼
API da aplicação
    │
    ▼
Frontend
```

Isso evita depender da disponibilidade da API externa para cada acesso do usuário.

---

# 11. Estratégia de IDs

Nunca utilizar somente o ID de uma API como chave primária.

### Incorreto

```text
id = 12345
```

O `12345` pode existir simultaneamente em diferentes APIs.

### Recomendado

```text
id interno:
01JXXXXXXXXXXXXXXX

external_ids:
{
  anilist: 30002,
  mal: 12345,
  kitsu: "abc123",
  mangadex: "uuid..."
}
```

Assim o sistema consegue identificar que diferentes registros externos representam a mesma obra.

---

# 12. Deduplicação

Uma mesma obra pode aparecer com títulos diferentes.

Exemplo:

```text
Berserk
ベルセルク
Берсерк
Berserk: The Prototype
```

A deduplicação deve considerar, preferencialmente:

1. ID externo conhecido.
2. ISBN, quando disponível.
3. Título original.
4. Autor.
5. Ano de publicação.
6. Similaridade de títulos.

Não utilizar somente o título em inglês.

---

# 13. Pipeline recomendado

```text
              AniList
                 │
                 ▼
          ┌─────────────┐
          │ Normalizer  │
          └──────┬──────┘
                 │
                 ▼
          ┌─────────────┐
          │ Deduplicator│
          └──────┬──────┘
                 │
                 ▼
          ┌─────────────┐
          │   Database  │
          └──────┬──────┘
                 ▲
                 │
          ┌──────┴──────┐
          │    Jikan    │
          │  fallback   │
          └─────────────┘
```

---

# 14. Ordem de implementação

## Fase 1 — MVP

Implementar:

```text
AniList
   ↓
Manga Repository
   ↓
Banco de dados
   ↓
API própria
```

Campos:

```text
id
title
synopsis
cover
status
chapters
volumes
genres
authors
score
start_date
end_date
```

---

## Fase 2 — Fallback

Adicionar:

```text
Jikan
```

Fluxo:

```text
AniList
   │
   ├── sucesso ──► Banco
   │
   └── erro
        │
        ▼
      Jikan
        │
        ▼
      Banco
```

---

## Fase 3 — Enriquecimento

Adicionar:

```text
Kitsu
MangaDex
MyAnimeList
```

Cada API deve ser tratada como uma fonte adicional, não como dependência obrigatória.

---

# 15. Interface de abstração

A aplicação deve evitar chamar AniList/Jikan diretamente no restante do código.

Criar uma interface:

```typescript
interface MangaProvider {

  search(query: string): Promise<Manga[]>;

  getById(id: string): Promise<Manga | null>;

  getByExternalId(
    provider: MangaSource,
    id: string
  ): Promise<Manga | null>;

  getPopular(
    page?: number
  ): Promise<Manga[]>;

  getRecent(
    page?: number
  ): Promise<Manga[]>;
}
```

Implementações:

```text
MangaProvider
      │
      ├── AniListProvider
      ├── JikanProvider
      ├── KitsuProvider
      ├── MyAnimeListProvider
      └── MangaDexProvider
```

---

# 16. Recomendação final

Para a primeira versão:

```text
🥇 AniList
   Fonte principal

🥈 Jikan
   Fallback / segunda fonte

🥉 Kitsu
   Futuro enriquecimento

   MangaDex
   Futuro enriquecimento

   MyAnimeList
   Integração específica
```

### Stack sugerida

```text
Frontend
   ↓
Sua API
   ↓
MangaService
   ↓
MangaProvider
   ├── AniList
   ├── Jikan
   └── Kitsu
   ↓
PostgreSQL
```

### Princípio principal

> **O banco de dados da aplicação deve ser a fonte de verdade do sistema. As APIs externas devem ser tratadas como fontes de dados, e não como o banco de dados da aplicação.**

Isso permite trocar uma API posteriormente sem precisar reescrever o frontend ou a regra de negócio.

---

## Links de documentação

- AniList: https://docs.anilist.co/
- Jikan: https://docs.jikan.moe/
- Kitsu: https://kitsu.docs.apiary.io/
- MyAnimeList API: https://myanimelist.net/apiconfig/references/api/v2
- MangaDex API: https://api.mangadex.org/docs/