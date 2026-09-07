# 7. Modelagem de Dados

A modelagem deve priorizar normalização, integridade referencial, extensibilidade, índices adequados, baixa duplicidade e independência de fornecedores externos.

## 7.1 Tabelas Principais

### yomi_mangas

Tabela central do catálogo.

Responsável por armazenar:

- título principal;
- título original;
- sinopse;
- status;
- datas;
- quantidade conhecida de capítulos;
- quantidade conhecida de volumes;
- timestamps de sincronização;
- demais metadados próprios do Yomi.

### yomi_manga_external_ids

Tabela responsável pelos identificadores externos.

```
id
manga_id
provider
external_id
created_at
updated_at
```

Permite relacionar uma obra a múltiplos provedores sem acoplar o catálogo principal a apenas MAL/AniList.

### yomi_criadores

Cadastro normalizado de autores, ilustradores, roteiristas e outros criadores.

### yomi_manga_criadores

Relacionamento entre obras e criadores, incluindo papel/função quando disponível.

### yomi_personagens

Cadastro persistente de personagens.

### yomi_manga_personagens

Relacionamento entre mangas e personagens, incluindo papel quando disponível.

### yomi_generos

Catálogo normalizado de gêneros.

### yomi_manga_generos

Relacionamento entre mangas e gêneros.

### yomi_capitulos

Armazena capítulos conhecidos, número, título, identificador externo, data de publicação e metadados de sincronização.

### yomi_progresso_usuario

Tabela de progresso resumido por usuário e obra:

```
user_id
manga_id
status
ultimo_capitulo_lido
ultimo_volume_lido
nota
favorito
observacoes
started_at
completed_at
created_at
updated_at
```

Deve existir uma restrição de unicidade:

```sql
UNIQUE(user_id, manga_id)
```

### yomi_usuario_capitulos

Registro granular:

```
user_id
manga_id
capitulo_id
read_at
created_at
```

### yomi_midias

```
id
entity_type
entity_id
type
source_url
storage_path
mime_type
width
height
checksum
status
downloaded_at
created_at
updated_at
```

Deve suportar mídias de mangas, personagens e criadores.

### yomi_sync_logs

Histórico de sincronizações, registrando obra, provedor, operação, resultado, erro, duração e timestamps.