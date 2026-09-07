# 4. Arquitetura de Integração

A aplicação deverá utilizar uma arquitetura baseada em abstração de provedores, evitando que as regras de negócio dependam diretamente de Jikan ou AniList.

```text
                         Yomi Application
                               |
                        MangaSyncService
                               |
                         Sync Manager
                               |
              +----------------+----------------+
              |                                 |
       Local Repository                  Provider Resolver
              |                                 |
         PostgreSQL                  +-----------+-----------+
                                    |                       |
                              JikanProvider          AniListProvider
                                    |                       |
                                  Jikan                  AniList
```

## 4.1 Ordem de Consulta

1. Banco de dados local.
2. Jikan/MyAnimeList.
3. AniList GraphQL.
4. Estado degradado com dados locais parciais, quando disponíveis.

O banco local deve ser considerado a fonte operacional primária. Os provedores externos funcionam como fontes de aquisição e atualização.

## 4.2 Comportamento de Dados Locais

Se a obra existir localmente:

- retornar os dados locais imediatamente quando estiverem atualizados;
- se estiverem desatualizados, permitir que os dados locais sejam utilizados enquanto uma sincronização seja executada;
- não remover dados existentes em decorrência de falha externa;
- não substituir campos válidos por null simplesmente porque um provedor não retornou determinado campo.