# 18. Critérios de Aceite

- [ ] Uma obra existente localmente é retornada sem depender de API externa.
- [ ] Uma obra inexistente pode ser obtida via Jikan e persistida.
- [ ] Uma falha simulada do Jikan aciona o AniList.
- [ ] Uma falha de ambos os provedores não remove dados existentes.
- [ ] Uma obra stale pode ser atualizada.
- [ ] A atualização não bloqueia desnecessariamente o acesso aos dados locais.
- [ ] Criadores são persistidos sem duplicação indevida.
- [ ] Personagens são persistidos sem duplicação indevida.
- [ ] Gêneros são normalizados.
- [ ] Títulos alternativos são persistidos.
- [ ] O usuário possui somente um registro de progresso por obra.
- [ ] O progresso granular pode ser registrado independentemente do progresso resumido.
- [ ] Jobs de mídia podem falhar e ser reprocessados.
- [ ] Rate limits são tratados adequadamente.
- [ ] Timeouts são tratados adequadamente.
- [ ] Logs permitem identificar falhas de sincronização.
- [ ] Migrations podem ser executadas em ambiente limpo.
- [ ] Testes automatizados cobrem os principais fluxos e falhas.