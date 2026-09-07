# 22. Resultado Esperado

Ao final desta etapa, o Yomi deverá possuir um núcleo backend independente da interface, capaz de:

```text
                 +---------------------+
                 |    Portal Laravel   |
                 +----------+----------+
                            |
                            v
                    +---------------+
                    |  Yomi Domain  |
                    +-------+-------+
                            |
              +-------------+-------------+
              |             |             |
              v             v             v
         PostgreSQL    SyncManager    Progress
                            |
                      +-----+-----+
                      |           |
                      v           v
                    Jikan      AniList
                            |
                            v
                     Media / Storage
```

A camada resultante deverá ser suficientemente estável para que a etapa posterior de UI possa apenas consumir os serviços e dados fornecidos pelo núcleo Yomi, sem precisar conhecer detalhes das APIs externas, da estratégia de fallback ou do armazenamento de mídias.

> **Observação:** A interface visual do Yomi deverá ser especificada em uma etapa posterior, utilizando os serviços e contratos definidos nesta especificação como base.