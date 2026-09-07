# Yomi — Índice de Documentação

> Especificação de Solicitação de Desenvolvimento — Módulo de Rastreamento e Catalogação de Mangás (Backend e Infraestrutura).

Documentação organizada por tipo de conteúdo:

```
docs/
├── 00-indice.md            Este índice (entrada principal)
├── especificacao/          Especificação de solicitação (seções 1–22, com status no nome)
├── implementacao/          Status e resumo da implementação no código
├── api/                    Referência da API REST
├── referencias/            Pesquisas e propostas de integração externa
└── _ui/                    PRD e protótipos de interface (modelo usado para o layout)
```

> **Resumo geral de implementação:** [`implementacao/99-resumo-da-implementacao.md`](./implementacao/99-resumo-da-implementacao.md)
>
> **API REST:** [`api/API_DOCUMENTATION.md`](./api/API_DOCUMENTATION.md)

**Legenda de status:** `concluido` — implementado, verificado no código (testes verdes: `php artisan test tests/Unit/Yomi/` — 24 testes passando).

## Especificação

| # | Seção | Status | Arquivo |
|---|-------|--------|---------|
| 1 | Objetivo da Solicitação | concluido | [`especificacao/01-objetivo-concluido.md`](./especificacao/01-objetivo-concluido.md) |
| 2 | Escopo | concluido | [`especificacao/02-escopo-concluido.md`](./especificacao/02-escopo-concluido.md) |
| 3 | Requisitos Funcionais | concluido | [`especificacao/03-requisitos-funcionais-concluido.md`](./especificacao/03-requisitos-funcionais-concluido.md) |
| 4 | Arquitetura de Integração | concluido | [`especificacao/04-arquitetura-de-integracao-concluido.md`](./especificacao/04-arquitetura-de-integracao-concluido.md) |
| 5 | Contrato de Provedor | concluido | [`especificacao/05-contrato-de-provedor-concluido.md`](./especificacao/05-contrato-de-provedor-concluido.md) |
| 6 | Provedores Externos | concluido | [`especificacao/06-provedores-externos-concluido.md`](./especificacao/06-provedores-externos-concluido.md) |
| 7 | Modelagem de Dados | concluido | [`especificacao/07-modelagem-de-dados-concluido.md`](./especificacao/07-modelagem-de-dados-concluido.md) |
| 8 | Dados Atemporais x Sincronizáveis | concluido | [`especificacao/08-dados-atemporais-e-sincronizaveis-concluido.md`](./especificacao/08-dados-atemporais-e-sincronizaveis-concluido.md) |
| 9 | Estratégia de Sincronização | concluido | [`especificacao/09-estrategia-de-sincronizacao-concluido.md`](./especificacao/09-estrategia-de-sincronizacao-concluido.md) |
| 10 | Estado Degradado | concluido | [`especificacao/10-estado-degradado-concluido.md`](./especificacao/10-estado-degradado-concluido.md) |
| 11 | Jobs e Processamento Assíncrono | concluido | [`especificacao/11-jobs-e-processamento-assincrono-concluido.md`](./especificacao/11-jobs-e-processamento-assincrono-concluido.md) |
| 12 | Espelhamento e Armazenamento de Mídia | concluido | [`especificacao/12-espelhamento-e-armazenamento-de-midia-concluido.md`](./especificacao/12-espelhamento-e-armazenamento-de-midia-concluido.md) |
| 13 | Serviços e Componentes | concluido | [`especificacao/13-servicos-e-componentes-concluido.md`](./especificacao/13-servicos-e-componentes-concluido.md) |
| 14 | Segurança e Integridade | concluido | [`especificacao/14-seguranca-e-integridade-concluido.md`](./especificacao/14-seguranca-e-integridade-concluido.md) |
| 15 | Rate Limit e Retry | concluido | [`especificacao/15-rate-limit-e-retry-concluido.md`](./especificacao/15-rate-limit-e-retry-concluido.md) |
| 16 | Observabilidade e Logs | concluido | [`especificacao/16-observabilidade-e-logs-concluido.md`](./especificacao/16-observabilidade-e-logs-concluido.md) |
| 17 | Testes | concluido | [`especificacao/17-testes-concluido.md`](./especificacao/17-testes-concluido.md) |
| 18 | Critérios de Aceite | concluido | [`especificacao/18-criterios-de-aceite-concluido.md`](./especificacao/18-criterios-de-aceite-concluido.md) |
| 19 | Entregáveis | concluido | [`especificacao/19-entregaveis-concluido.md`](./especificacao/19-entregaveis-concluido.md) |
| 20 | Ordem Recomendada de Implementação | concluido | [`especificacao/20-ordem-de-implementacao-concluido.md`](./especificacao/20-ordem-de-implementacao-concluido.md) |
| 21 | Requisitos Não Funcionais | concluido | [`especificacao/21-requisitos-nao-funcionais-concluido.md`](./especificacao/21-requisitos-nao-funcionais-concluido.md) |
| 22 | Resultado Esperado | concluido | [`especificacao/22-resultado-esperado-concluido.md`](./especificacao/22-resultado-esperado-concluido.md) |