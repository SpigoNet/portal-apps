# 9. Estratégia de Sincronização

```text
Usuário/serviço solicita obra
          |
          v
Existe no banco local?
       /       \
     SIM        NÃO
      |          |
      v          v
Está stale?    Jikan
   /    \         |
 NÃO    SIM    sucesso?
  |      |      /    \
  |      |    SIM     NÃO
  |      |     |       |
  |      +-----+       v
  |            |     AniList
  v            v       |
Retorna     Atualiza  sucesso?
local       dados     /    \
                    SIM    NÃO
                     |       |
                     v       v
                  Persiste  Estado
                            degradado
```

Regras:

- A consulta local deve ser sempre priorizada.
- Dados locais válidos nunca devem ser apagados por falha externa.
- Sincronizações demoradas devem ser executadas em background quando possível.
- Dados mínimos necessários devem ser validados antes da criação de novos registros.
- Dados externos devem ser normalizados antes da persistência.
- A aplicação deve saber qual provedor foi utilizado.
- Falhas de um provedor não devem interromper o domínio inteiro.