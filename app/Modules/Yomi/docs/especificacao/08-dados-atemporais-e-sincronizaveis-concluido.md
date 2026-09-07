# 8. Dados Atemporais x Dados Sincronizáveis

Nem todos os metadados externos devem ser tratados como permanentes.

## Dados relativamente estáveis

- autores;
- personagens;
- relacionamentos;
- identificadores externos;
- títulos históricos.

## Dados sujeitos a atualização

- sinopse;
- status de publicação;
- número de capítulos;
- número de volumes;
- gêneros;
- capas;
- datas;
- informações de publicação.

Quando relevante, manter:

```text
last_synced_at
next_sync_at
sync_status
source_updated_at
```