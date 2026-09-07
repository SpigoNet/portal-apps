# 17. Testes

## 17.1 Testes de Domínio

Validar:

- cadastro de obras;
- relacionamento entre obras e criadores;
- relacionamento entre obras e personagens;
- relacionamento entre obras e gêneros;
- criação e atualização de progresso;
- restrição de progresso duplicado;
- marcação de capítulos;
- notas;
- favoritos;
- status de leitura.

## 17.2 Testes de Integração

Simular:

- sucesso do Jikan;
- timeout do Jikan;
- rate limit do Jikan;
- resposta inválida do Jikan;
- sucesso do AniList;
- falha do AniList;
- fallback entre provedores;
- indisponibilidade simultânea.

## 17.3 Testes de Mídia

Validar:

- download bem-sucedido;
- MIME inválido;
- arquivo acima do limite;
- timeout;
- retry;
- preservação da mídia existente;
- checksum/deduplicação quando implementados.