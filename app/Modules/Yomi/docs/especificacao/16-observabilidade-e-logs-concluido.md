# 16. Observabilidade e Logs

Registrar, quando aplicável:

```text
manga_id
provider
operation
status
started_at
finished_at
duration
http_status
error_type
error_message
attempt
```

Os logs devem permitir identificar:

- qual provedor falhou;
- quantidade de tentativas;
- última sincronização;
- causa da falha;
- acionamento do fallback;
- resultado do download de mídia.