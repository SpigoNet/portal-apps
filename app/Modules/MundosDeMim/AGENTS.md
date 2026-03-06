# Módulo MundosDeMim — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **MundosDeMim** é um serviço de geração diária de arte personalizada por IA. Para cada usuário inscrito, o sistema gera imagens artísticas personalizadas com base em:

- **Perfil biométrico** do usuário (altura, peso, tipo corporal, cor dos olhos)
- **Pessoas relacionadas** (cônjuge, filhos) para temas de casal/família
- **Temas** sazonais ou permanentes (Natal, Halloween, etc.)
- **Prompts de IA** configurados por administradores

As imagens são entregues diariamente via **WhatsApp** ou **Telegram**.

**Planos:**
- **Eco (gratuito):** Entrega via Telegram Bot
- **Prime (R$ 34,90/mês):** Entrega via WhatsApp API

**Acesso:** Landing page pública. Funcionalidades requerem autenticação.

---

## 2. Estrutura de Diretórios

```
app/Modules/MundosDeMim/
├── Console/
│   └── Commands/
│       └── RechargeCredits.php              # Comando: recarregar créditos diários
├── Http/
│   └── Controllers/
│       ├── ConfigController.php             # Configurações do usuário
│       ├── DashboardController.php          # Landing + dashboard do usuário
│       ├── EstilosController.php            # Seleção de temas/estilos
│       ├── GaleriaController.php            # Galeria de imagens geradas
│       ├── PerfilBiometricoController.php   # Perfil físico do usuário
│       ├── PessoasRelacionadasController.php # Cônjuge/filhos
│       ├── PlaygroundController.php         # Teste de prompts/geração
│       └── Admin/
│           ├── AdminGalleryController.php   # Galeria pública
│           ├── AdminPromptController.php    # CRUD de prompts
│           ├── AdminThemeController.php     # CRUD de temas
│           ├── AdminUserGalleryController.php # Galeria dos usuários
│           └── PromptImporterController.php # Importação em lote de prompts
├── Models/
│   ├── AIProvider.php                      # Provedor de IA do módulo
│   ├── DailyGeneration.php                 # Registro de geração diária
│   ├── Prompt.php                          # Template de prompt
│   ├── PromptRequirement.php               # Requisito/variação do prompt
│   ├── RelatedPerson.php                   # Pessoa relacionada ao usuário
│   ├── Theme.php                           # Tema/estilo de geração
│   ├── ThemeExample.php                    # Exemplos visuais do tema
│   ├── UserAttribute.php                   # Atributos físicos do usuário
│   └── UserAiSetting.php                   # Configurações de IA do usuário
├── Database/
│   └── Migrations/                         # Migrations do módulo
├── Services/                               # Serviços de geração e entrega
├── resources/
│   └── views/
│       ├── landing.blade.php               # Página pública de apresentação
│       ├── index.blade.php                 # Dashboard do usuário
│       ├── perfil/index.blade.php          # Perfil biométrico
│       ├── pessoas/                        # Gerenciar pessoas relacionadas
│       ├── galeria/index.blade.php         # Galeria do usuário
│       ├── estilos/index.blade.php         # Seleção de temas
│       ├── playground/index.blade.php      # Teste de geração
│       ├── admin/themes/                   # Admin: CRUD de temas
│       ├── admin/prompts/                  # Admin: CRUD de prompts
│       ├── admin/import/                   # Admin: importador
│       ├── admin/gallery/                  # Admin: galeria pública
│       └── admin/user-gallery/             # Admin: galeria dos usuários
├── docs/
│   └── Documentação de Projeto_ Mundos de Mim.md
├── MundosDeMimServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/mundos-de-mim`  
**Nome base:** `mundos-de-mim.*`

### Rotas Públicas

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/mundos-de-mim` | `mundos-de-mim.landing` | Página de apresentação pública |

### Rotas do Usuário (middleware: `auth`)

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/mundos-de-mim/dashboard` | `mundos-de-mim.dashboard` | Dashboard com stats e atalhos |
| GET | `/mundos-de-mim/meu-perfil` | `mundos-de-mim.perfil` | Ver/editar perfil biométrico |
| POST | `/mundos-de-mim/meu-perfil` | `mundos-de-mim.perfil.store` | Salvar perfil |
| POST | `/mundos-de-mim/meu-perfil/analisar` | `mundos-de-mim.perfil.analisar` | Análise IA do perfil |
| GET | `/mundos-de-mim/pessoas` | `mundos-de-mim.pessoas.index` | Lista pessoas relacionadas |
| GET | `/mundos-de-mim/pessoas/adicionar` | `mundos-de-mim.pessoas.create` | Formulário de adição |
| POST | `/mundos-de-mim/pessoas` | `mundos-de-mim.pessoas.store` | Salvar pessoa |
| POST | `/mundos-de-mim/pessoas/{id}/toggle` | `mundos-de-mim.pessoas.toggle` | Ativar/desativar |
| GET | `/mundos-de-mim/galeria` | `mundos-de-mim.galeria` | Galeria de imagens geradas |
| GET | `/mundos-de-mim/estilos` | `mundos-de-mim.estilos` | Seleção de temas |
| POST | `/mundos-de-mim/estilos/toggle` | `mundos-de-mim.estilos.toggle` | Ativar/desativar tema |
| GET | `/mundos-de-mim/playground` | `mundos-de-mim.playground` | Interface de teste |
| POST | `/mundos-de-mim/playground` | `mundos-de-mim.playground.generate` | Gerar preview |
| POST | `/mundos-de-mim/playground/refinar` | `mundos-de-mim.playground.refinar` | Refinar geração |
| POST | `/mundos-de-mim/playground/select-user` | `mundos-de-mim.playground.select-user` | Testar com outro usuário |

### Rotas Admin (middleware: `auth`, `admin`)

**Prefixo:** `/mundos-de-mim/admin`

| Recursos | URI Base | Descrição |
|---------|----------|-----------|
| Temas | `/mundos-de-mim/admin/themes` | CRUD completo de temas |
| Exemplos de temas | `/mundos-de-mim/admin/themes/{id}/examples` | Gerenciar exemplos visuais |
| Prompts | `/mundos-de-mim/admin/themes/{id}/prompts` | CRUD de prompts por tema |
| Importador | `/mundos-de-mim/admin/import` | Importação em lote de prompts |
| Galeria pública | `/mundos-de-mim/admin/gallery` | Gerenciar galeria pública |
| Galeria usuários | `/mundos-de-mim/admin/user-gallery` | Ver/reenviar imagens dos usuários |

---

## 4. Controllers

### Usuário

#### `DashboardController`
- Landing page pública com pitch do produto
- Dashboard autenticado com estatísticas (gerações realizadas, temas ativos)

#### `PerfilBiometricoController`
- Exibe e salva atributos físicos: altura, peso, tipo corporal, cor dos olhos, tipo de cabelo
- Análise via IA para caracterização personalizada

#### `PessoasRelacionadasController`
- Gerencia cônjuge/filhos/outros para temas de casal ou família
- Suporta upload de foto da pessoa relacionada
- Ativar/desativar pessoas para inclusão nas gerações

#### `GaleriaController`
- Exibe histórico de imagens geradas com filtros e ordenação

#### `EstilosController`
- Lista temas disponíveis com previews
- Permite ao usuário selecionar/desmarcar temas de interesse

#### `PlaygroundController`
- Interface para administradores testarem prompts antes de publicar
- Geração de preview, refinamento e seleção de usuário de teste

### Admin

#### `AdminThemeController`
CRUD de temas. Campos: nome, slug, classificação etária (kids/teen/adult), sazonal ou permanente, datas de vigência.

#### `AdminPromptController`
CRUD de prompts vinculados a um tema. Prompts são os textos enviados ao modelo de IA.

#### `PromptImporterController`
Importação em lote de prompts a partir de arquivos com análise prévia do conteúdo.

#### `AdminGalleryController`
Gerencia a galeria pública: copia imagens de usuários para exibição pública ou as remove.

#### `AdminUserGalleryController`
Visão administrativa das imagens de cada usuário, com opção de reenviar via WhatsApp/Telegram.

#### IA no módulo
Este módulo não administra provedores/modelos de IA localmente. Toda configuração deve ser feita no portal/admin e o consumo ocorre via `App\Modules\Admin\Services\AiProviderService`.

---

## 5. Models

### `Theme`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `name` | string | Nome do tema |
| `slug` | string | Identificador URL |
| `age_rating` | string | `kids`, `teen`, `adult` |
| `is_seasonal` | boolean | Tema sazonal (ativo por período) |
| `starts_at` | date | Início de vigência sazonal |
| `ends_at` | date | Fim de vigência sazonal |

### `Prompt`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `theme_id` | integer | FK para `Theme` |
| `prompt_text` | text | Texto do prompt de IA |

### `PromptRequirement`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `prompt_id` | integer | FK para `Prompt` |
| `type` | string | Ex: `requires_couple`, `age_range` |

### `RelatedPerson`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `name` | string | Nome da pessoa |
| `relationship` | string | `spouse`, `child`, `other` |
| `photo_path` | string | Caminho da foto |
| `is_active` | boolean | Incluída nas gerações |

### `UserAttribute`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `height` | decimal | Altura (cm) |
| `weight` | decimal | Peso (kg) |
| `body_type` | string | Tipo corporal |
| `eye_color` | string | Cor dos olhos |
| `hair_type` | string | Tipo de cabelo |

### `DailyGeneration`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `theme_id` | integer | FK para `Theme` |
| `prompt_id` | integer | FK para `Prompt` |
| `image_url` | string | URL da imagem gerada |
| `delivery_method` | string | `whatsapp` ou `telegram` |
| `status` | string | `pending`, `sent`, `failed` |

### `UserAiSetting`
Preferências específicas de IA para o usuário (modelo preferido, qualidade, etc.).

### `ThemeExample`
Imagens de exemplo de cada tema para exibição na seleção de estilos.

---

## 6. Fluxo de Geração

```
Cron diário executa RechargeCredits.php
→ Para cada usuário ativo com plano:
  1. Seleciona tema ativo (sazonal prioritário)
  2. Filtra prompts compatíveis com perfil do usuário
  3. Gera imagem via API de IA (provider configurado)
  4. Salva DailyGeneration com URL da imagem
  5. Entrega via WhatsApp ou Telegram
```

---

## 7. Tabelas do Banco de Dados

Prefixo: `mundos_de_mim_`

- `mundos_de_mim_user_attributes`
- `mundos_de_mim_related_people`
- `mundos_de_mim_themes`
- `mundos_de_mim_prompt_requirements`
- `mundos_de_mim_prompts`
- `mundos_de_mim_daily_generations`
- `mundos_de_mim_ai_providers`
- `mundos_de_mim_user_ai_settings`

---

## 8. Notas para Agentes de IA

- A documentação de produto detalhada está em `docs/Documentação de Projeto_ Mundos de Mim.md`.
- A configuração de provedores/modelos de IA é centralizada no portal/admin; este módulo apenas consome essa definição via `AiProviderService`.
- O controle de admin é via Gate `admin-do-app` (portal_app_id: 10).
- As migrations estão dentro do módulo em `Database/Migrations/` (note o 'D' maiúsculo).
- Temas sazonais têm datas de vigência — só aparecem no período configurado.
- `PromptRequirement` define restrições: ex. um prompt que requer cônjuge só é selecionado se o usuário tiver `RelatedPerson` com `relationship = 'spouse'` ativo.
