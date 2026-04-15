# Mithril — Guia Rápido

## Testes
```bash
php artisan test app/Modules/Mithril/tests/
```

## Arquitetura (Web)
- **Prefixo:** `/mithril`
- **Middleware:** `web`, `auth`, `RegistrarAcesso:Mithril`
- **Nomebase:** `mithril.*`

## API (JSON)
- **Prefixo:** `/api/mithril`
- **Auth:** `auth:sanctum`
- **Controllers:** `app/Modules/Mithril/Http/Controllers/Api/`
- **Rotas:** `routes/api.php`

### Endpoints API
| Método | Rota | Descrição |
|--------|------|----------|
| GET | `/api/mithril/dashboard` | Dados agregados |
| GET/POST | `/api/mithril/contas` | CRUD Contas |
| GET/POST | `/api/mithril/transacoes` | Lista/Criar transações |
| GET/POST | `/api/mithril/pre-transacoes` | CRUD Pré-transações |
| POST | `/api/mithril/pre-transacoes/{id}/toggle` | Ativar/desativar |
| POST | `/api/mithril/pre-transacoes/{id}/efetivar` | Criar transação |
| GET | `/api/mithril/lancamentos` | Lista combinada |
| GET/POST | `/api/mithril/fechamentos` | Fechamentos |

## Onde estão as coisas
- **Migrations:** `database/migrations/` (não dentro do módulo)
- **Models:** `app/Modules/Mithril/Models/`
- **Controllers:** `app/Modules/Mithril/Http/Controllers/`

## Armadilhas Comuns
1. **Global Scope:** Todos os models filtram automaticamente por `user_id` autenticado
2. **Pré-transação ≠ Transação:** Pré-transações precisam ser confirmadas e efetivadas para gerar `Transacao` real
3. **Rotas placeholders:** `/mithril/transacao/criar` e `/mithril/fatura/{id}` ainda não implementadas
4. **Valor negativo:** Em `Transacao`, valores de saída são negativos

## Fluxo Principal
```
Conta → Pré-transação (parcelada/recorrente) → Confirmar → Efetivar → Transação
```