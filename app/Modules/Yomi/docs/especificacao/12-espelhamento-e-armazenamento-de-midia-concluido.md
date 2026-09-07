# 12. Espelhamento e Armazenamento de Mídia

O Yomi deverá armazenar localmente as mídias relevantes para reduzir a dependência de URLs externas.

## Requisitos

- armazenar URL original;
- armazenar caminho local;
- validar HTTP status;
- validar MIME type;
- aplicar limite de tamanho;
- gerar caminhos seguros e determinísticos;
- registrar checksum quando aplicável;
- permitir retry;
- não apagar automaticamente mídia local por indisponibilidade da origem.

O checksum pode ser utilizado para deduplicação de arquivos idênticos.