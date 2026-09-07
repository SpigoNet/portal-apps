# Product Requirements Document (PRD) — Yomi: Sua Jornada em Mangás

**Versão:** 1.0  
**Data:** Março de 2025  
**Status:** Aprovado para Desenvolvimento / Especificação de Interface  
**Design System Associado:** Sumi Editorial (`#0E0E13`, `#131318`, `#E53935`, `#F4F1EA`)  
**Plataformas:** Web Desktop & Mobile PWA / iOS / Android  

---

## 1. Visão Geral & Proposta de Valor

### 1.1 O que é o Yomi?
O **Yomi** (読書録) é uma plataforma contemporânea de gerenciamento, acompanhamento e registro de leitura de mangás. A proposta central é transformar o acompanhamento de mangás em uma experiência tão fluida, envolvente e visualmente prazerosa quanto acompanhar séries em plataformas de entretenimento (como TV Time ou Letterboxd), sem fricções ou complexidade desnecessária.

> *"Aqui está toda a minha jornada de leitura."*

O produto centraliza o que hoje se encontra fragmentado entre abas abertas de navegador, anotações pessoais, planilhas e memórias dispersas, unindo:
1. **Biblioteca pessoal e catalogação intuitiva**
2. **Atualização de progresso em 1 clique (Zero-Friction)**
3. **Radar de novos capítulos e simulpub**
4. **Diário de leitura e estatísticas motivacionais**

### 1.2 O que o Yomi NÃO é (Anti-Metas)
- Não é um leitor pirata de scans com carregamento ilegal de páginas completas.
- Não é uma enciclopédia enciclopédica desorganizada ou poluída.
- Não é um fórum otaku carregado de neon, gifs piscantes ou tipografia caricata.

---

## 2. Princípios de Marca & Identidade Visual

| Pilar | Diretriz | Implementação na UI |
| :--- | :--- | :--- |
| **Moderno & Editorial** | Produto digital de ponta com alma de revista literária japonesa contemporânea. | Tipografia *Plus Jakarta Sans*, espaçamento generoso, contrastes equilibrados. |
| **Sumi & Papel** | Base escura profunda inspirada na tinta Sumi-e e páginas off-white. | Backgrounds `#0E0E13` e `#131318`; texto em `#F4F1EA` (reduz fadiga ocular). |
| **Vermelho Carmim ("Olhe Aqui")** | O vermelho japonês é usado exclusivamente como vetor de foco e atenção. | Apenas para barras de progresso, alertas de novos capítulos e botões de ação primária (`#E53935`). |
| **Acolhedor & Pessoal** | A biblioteca deve parecer uma estante real do próprio usuário. | Capas em proporção 2:3 em evidência editorial sem poluição de badges excessivas. |

---

## 3. Personas & Casos de Uso

### 3.1 Personas Principais
1. **O Leitor Semanal ("Simulpub Follower"):** Acompanha 10 a 30 obras simultaneamente que lançam capítulos toda semana. Precisa saber instantaneamente: *"Quais obras minhas tiveram capítulo novo hoje?"* e avançar em 1 toque.
2. **O Maratonista ("Binge Reader"):** Lê arcos inteiros em dias livres. Precisa marcar múltiplos capítulos, consultar volumes e registrar onde parou sem ter que abrir múltiplas telas.
3. **O Colecionador / Estatístico:** Tem orgulho do volume lido ao longo dos anos. Quer ver seu "DNA de Leitor", distribuição de gêneros (Seinen vs. Shonen), dias consecutivos (streaks) e heatmap de hábitos.

---

## 4. Requisitos Funcionais & Especificação de Telas

### 4.1 Início & Dashboard de Leitura (Home)
- **Hero "Continue Lendo":**
  - Exibição da obra prioritária com capa 2:3, arco atual e porcentagem concluída.
  - **CTA Primário em 1 Toque:** `+1 Marcar Cap. [N+1] como Lido`.
  - Atalhos secundários: link para leitor externo oficial (MangaPlus, Viz) e histórico de capítulos.
- **Fila Ativa (Reading Queue):**
  - Lista/Carrossel horizontal com as 2-4 obras seguintes na fila. Cada card permite incrementar `+1 Cap.` diretamente.
- **Radar de Novos Capítulos (Simulpub):**
  - Feed cronológico com notificações de novos lançamentos sincronizados.
  - Check rápido para marcar como lido diretamente do feed.
- **Resumo Semanal & Streak:**
  - Contagem de capítulos concluídos nos últimos 7 dias com micrográfico de barras e indicador de sequência ininterrupta (ex.: *19 dias 🔥*).

### 4.2 Minha Biblioteca (Library)
- **Filtros por Estado de Leitura:**
  - Segmentação em abas: `Todos`, `Lendo`, `Pretendo Ler`, `Concluídos`, `Pausados`, `Abandonados`.
  - Contadores discretos em cada aba (ex: *Lendo (12)*, *Concluídos (68)*).
- **Modos de Exibição:**
  - Grid visual com foco em capas 2:3 com badges sutis de demografia (*Seinen*, *Shonen*) e cadência (*Semanal*, *Mensal*).
  - Barra de progresso visual na base de cada card com porcentagem (`86% Lido`).
  - Ação rápida flutuante `+1` direto na capa para avançar sem abrir a página da obra.
- **Filtros e Busca Local:**
  - Campo de busca em tempo real (`Cmd/Ctrl + K`) com filtros combináveis por gênero, autor e ordem de última leitura.

### 4.3 Detalhes da Obra (Manga Details)
- **Bloco de Contexto Imediato ("Onde Você Parou"):**
  - No topo da página (zona prioritária), responde: *"Cap. 112 de 130 (86%)"* e CTA claro *"Marcar Cap. 113 como Lido"*.
- **Ficha Técnica & Metadados:**
  - Capa com banner em desfoque atmosférico, título original em kanji/romaji, autor/roteirista/desenhista, demografia, status de publicação e avaliação pessoal por estrelas.
- **Sinopse Retrátil:** Texto com toggle expansível sem empurrar os capítulos para fora da primeira dobra.
- **Índice de Capítulos & Volumes:**
  - Alternância entre visão de *Capítulos Individuais* e *Volumes Tankōbon*.
  - Indicador visual claro do "Próximo a Ler" destacado em vermelho carmim.
  - Lista com checkboxes de lido, data do registro e títulos de cada capítulo.
- **Recomendações Relacionadas:** Carrossel de obras do mesmo autor ou com tom estilístico similar baseado no algoritmo do Yomi.

### 4.4 Estatísticas da Jornada (Analytics & History)
- **Cartões de Métricas Globais (KPIs do Leitor):**
  - Capítulos lidos no ano / total, Obras concluídas, Sequência ativa (streak), Horas estimadas de imersão e Ritmo médio (ex.: *4.5 min/cap*).
- **Ritmo de Leitura Mensal:** Gráfico de evolução mensal com pico anual em destaque.
- **DNA do Leitor (Distribuição Demográfica):** Proporção de leitura por gêneros (ex.: *Seinen 42%*, *Shonen 28%*, *Sci-Fi 15%*).
- **Mapa de Constância (Activity Heatmap):** Matriz anual no estilo GitHub/hábitos, exibindo intensidade de leitura em 365 dias.
- **Hall de Conquistas (Badges):** Conquistas comemorativas desbloqueadas (*Leitor Noturno*, *Mestre de Seinen*, *Maratona Lendária*).
- **Diário de Leitura Recente:** Linha do tempo com log cronológico de marcos de leitura recentes.

---

## 5. Requisitos Não Funcionais & UX

1. **Latência de Ação Primária:** A marcação de capítulo deve responder em menos de 100ms na interface com feedback visual imediato (otimista).
2. **Design Thumb-Friendly (Mobile):** Todos os botões primários de avanço estão na metade inferior da tela, ao alcance do polegar.
3. **Consistência Cross-Platform:** Usuário transitando do Desktop para o Mobile deve reconhecer exatamente a mesma taxonomia, cores e estados de leitura.
4. **Armazenamento Offline & Sincronização:** Suporte a PWA offline-first para registro de leitura em viagens, sincronizando assim que reconectar.

---

## 6. Roteiro de Entregáveis & Status de Telas Criadas

| Tela | Formato | Status | Referência no Canvas |
| :--- | :--- | :--- | :--- |
| **Início & Dashboard** | Desktop | ✅ Concluído | `Yomi — Início & Dashboard de Leitura` |
| **Minha Biblioteca** | Desktop | ✅ Concluído | `Yomi — Minha Biblioteca de Mangás` |
| **Detalhes da Obra (Chainsaw Man)** | Desktop | ✅ Concluído | `Yomi — Detalhes da Obra (Chainsaw Man)` |
| **Estatísticas da Jornada** | Desktop | ✅ Concluído | `Yomi — Estatísticas da Jornada de Leitura` |
| **Início & Leitura Rápida** | Mobile | ✅ Concluído | `Yomi Mobile — Início & Leitura Rápida` |
| **Minha Biblioteca** | Mobile | ✅ Concluído | `Yomi Mobile — Minha Biblioteca` |
| **Detalhes da Obra** | Mobile | ✅ Concluído | `Yomi Mobile — Detalhes da Obra` |
| **Estatísticas & Jornada** | Mobile | ✅ Concluído | `Yomi Mobile — Estatísticas & Jornada` |

