# 6. Provedores Externos

## 6.1 Jikan / MyAnimeList

O Jikan deverá ser utilizado como provedor primário para:

- Busca de mangás.
- Obtenção de metadados.
- Identificação de autores.
- Identificação de personagens.
- Identificação de gêneros.
- Obtenção de capítulos quando disponíveis.
- Obtenção de URLs de mídia.
- Atualização de dados existentes.

## 6.2 AniList

O AniList GraphQL deverá funcionar como provedor secundário e ser acionado principalmente quando o provedor primário:

- estiver indisponível;
- apresentar timeout;
- retornar erro temporário;
- atingir rate limit;
- não possuir determinado recurso necessário.