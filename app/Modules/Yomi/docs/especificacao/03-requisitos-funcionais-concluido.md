# 3. Requisitos Funcionais

| ID | Requisito | Descrição |
|---|---|---|
| RF-01 | Cadastro de obras | O sistema deve cadastrar e persistir obras de mangá em uma base própria. |
| RF-02 | Identificação externa | Uma obra deve poder ser relacionada a identificadores de diferentes provedores, incluindo MAL/Jikan e AniList. |
| RF-03 | Títulos | Deve armazenar título principal, original e títulos alternativos. |
| RF-04 | Metadados | Deve persistir sinopse, status de publicação, datas, capítulos, volumes, gêneros e demais metadados disponíveis. |
| RF-05 | Criadores | Deve manter autores, ilustradores e demais criadores em estrutura normalizada e seus relacionamentos com as obras. |
| RF-06 | Personagens | Deve manter personagens em estrutura normalizada e seus relacionamentos com as obras. |
| RF-07 | Progresso | O usuário deve possuir progresso independente por obra. |
| RF-08 | Status de leitura | Suportar Lendo, Lido/Concluído, Pretendo Ler, Pausado e Abandonado. |
| RF-09 | Capítulos | Permitir registrar o último capítulo lido e, opcionalmente, capítulos individualmente concluídos. |
| RF-10 | Avaliação | Permitir nota pessoal por obra. |
| RF-11 | Diário | Permitir anotações pessoais associadas ao acompanhamento da obra. |
| RF-12 | Favoritos | Permitir marcar uma obra como favorita. |
| RF-13 | Sincronização | Obras inexistentes ou desatualizadas devem poder ser sincronizadas com provedores externos. |
| RF-14 | Fallback | Falhas do provedor primário devem acionar automaticamente o provedor secundário quando aplicável. |
| RF-15 | Cache local | Dados já persistidos devem continuar disponíveis quando os provedores externos estiverem indisponíveis. |
| RF-16 | Mídias | Imagens externas relevantes devem ser baixadas e armazenadas localmente de forma assíncrona. |
| RF-17 | Retry | Falhas transitórias de Jobs devem possuir política de novas tentativas. |
| RF-18 | Logs | O sistema deve registrar eventos relevantes de sincronização e falhas para diagnóstico. |