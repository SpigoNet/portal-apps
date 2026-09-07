# 11. Jobs e Processamento Assíncrono

Recomenda-se separar filas:

```text
yomi-sync
yomi-media
yomi-maintenance
```

## Jobs

| Job | Responsabilidade |
|---|---|
| SyncMangaJob | Sincronizar metadados de uma obra. |
| SyncMangaChaptersJob | Atualizar capítulos e informações de publicação. |
| DownloadMangaCoverJob | Baixar e validar capas. |
| DownloadCharacterImageJob | Espelhar imagens de personagens. |
| DownloadCreatorImageJob | Espelhar imagens de criadores. |
| RetryFailedMediaJob | Reprocessar mídias que falharam. |
| RefreshStaleMangaJob | Identificar obras desatualizadas e agendar atualização. |