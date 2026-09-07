# 2. Escopo

## 2.1 Dentro do Escopo

- Modelagem e criação das migrations PostgreSQL.
- Models, relacionamentos e repositories necessários ao domínio Yomi.
- Abstração de provedores externos de dados.
- Cliente para Jikan/MyAnimeList.
- Cliente para AniList GraphQL.
- Service Wrapper / Sync Manager para fallback e sincronização.
- Persistência e atualização de metadados.
- Controle de capítulos e progresso granular do usuário.
- Filas e Jobs para sincronização e download de mídias.
- Armazenamento local de capas, personagens e criadores.
- Controle de estado, retry, logs e falhas de sincronização.
- Testes automatizados do domínio e das integrações.

## 2.2 Fora do Escopo

- Telas de catálogo, detalhes de mangá, biblioteca e estatísticas.
- Componentes de frontend.
- Definição visual e UX.
- Design system.
- Prototipação de telas.
- Sistema de leitura de páginas/imagens de capítulos.
- Definição da infraestrutura de hospedagem/CDN definitiva.