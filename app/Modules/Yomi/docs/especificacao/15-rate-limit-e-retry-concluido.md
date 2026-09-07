# 15. Rate Limit e Retry

As integrações devem possuir:

- timeout;
- retry;
- backoff;
- identificação de rate limit;
- tratamento de erros HTTP;
- mecanismo de circuit breaking ou equivalente, se necessário;
- fallback para o provedor secundário.

O sistema deve evitar tempestades de requisições durante indisponibilidades.