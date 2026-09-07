# 13. Serviços e Componentes

| Componente | Responsabilidade |
|---|---|
| MangaService | Operações de domínio relacionadas às obras. |
| MangaSyncService / SyncManager | Orquestrar consulta local, stale check, provedores, normalização e persistência. |
| MangaProvider | Contrato comum para fontes externas. |
| JikanProvider | Integração com Jikan/MAL. |
| AniListProvider | Integração com AniList GraphQL. |
| MediaService | Aquisição, validação e persistência de mídias. |
| ProgressService | Status, capítulos lidos, nota, favoritos e diário. |
| Repositories | Isolar acesso aos dados persistidos. |
| DTOs | Transportar dados entre integrações e domínio. |
| Normalizers | Converter formatos externos para o modelo interno. |