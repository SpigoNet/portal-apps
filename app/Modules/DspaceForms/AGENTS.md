# Módulo DspaceForms — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **DspaceForms** é um editor visual para criação de configurações de formulários para o **DSpace** (sistema de repositório institucional). Permite:

- Criar e gerenciar **configurações XML** de formulários DSpace
- Construir **formulários** com linhas e campos personalizados
- Criar **listas de pares de valores** (dropdowns) reutilizáveis
- Definir **mapeamentos** de campos para propriedades DSpace
- Gerenciar **templates de email** para notificações do repositório
- **Exportar** toda a configuração como arquivo ZIP

**Acesso:** Autenticado (`auth`).  
**Middleware:** `RegistrarAcesso:DspaceForms`

---

## 2. Estrutura de Diretórios

```
app/Modules/DspaceForms/
├── Http/
│   └── Controllers/
│       ├── DspaceConfigSession.php          # Gerenciamento de sessão de config ativa
│       ├── DspaceEmailController.php        # CRUD de templates de email
│       ├── DspaceFormController.php         # CRUD de formulários
│       ├── DspaceFormFieldController.php    # Gerenciamento de campos
│       ├── DspaceFormMapController.php      # Mapeamento campo ↔ propriedade DSpace
│       ├── DspaceFormRowController.php      # Gerenciamento de linhas do formulário
│       ├── DspaceFormsController.php        # Controller principal + exportação
│       └── DspaceValuePairsListController.php # CRUD de listas de valores
├── Livewire/                                # Componentes Livewire (legado)
├── Models/
│   ├── DspaceEmailTemplate.php             # Template de email
│   ├── DspaceForm.php                      # Formulário
│   ├── DspaceFormField.php                 # Campo individual do formulário
│   ├── DspaceFormMap.php                   # Mapeamento campo → propriedade DSpace
│   ├── DspaceFormRow.php                   # Linha do formulário
│   ├── DspaceRelationField.php             # Campo de relacionamento
│   ├── DspaceValuePair.php                 # Item de lista dropdown
│   ├── DspaceValuePairsList.php            # Lista dropdown
│   ├── DspaceXmlConfiguration.php          # Configuração XML principal
│   ├── SubmissionProcess.php               # Processo de submissão
│   └── SubmissionStep.php                  # Etapa do processo
├── database/
│   └── migrations/                         # Migrations do módulo
├── resources/
│   └── views/
│       ├── configurations/                  # Criação/listagem de configurações
│       ├── forms/                           # CRUD de formulários + modal de campo
│       ├── emails/                          # CRUD de templates de email
│       ├── value-pairs-index.blade.php      # Gestão de listas de valores
│       ├── form-maps-index.blade.php        # Gestão de mapeamentos
│       └── components/                      # Componentes de layout
├── DspaceFormsServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/dspace-forms-editor`  
**Middleware:** `web`, `RegistrarAcesso:DspaceForms`  
**Nome base:** `dspace-forms.*`

### Configuração

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/dspace-forms-editor` | Lista de configurações disponíveis |
| GET | `/dspace-forms-editor/select-config/{id}` | Carregar configuração na sessão |
| POST | `/dspace-forms-editor/clear-config` | Limpar sessão |
| GET | `/dspace-forms-editor/configurations/create` | Criar nova configuração |
| POST | `/dspace-forms-editor/configurations` | Salvar nova configuração |
| POST | `/dspace-forms-editor/configurations/{id}/duplicate` | Clonar configuração |
| GET | `/dspace-forms-editor/export` | Exportar como ZIP |

### Listas de Pares de Valores (Dropdowns)

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/dspace-forms-editor/value-pairs` | Listar todas as listas |
| POST | `/dspace-forms-editor/value-pairs` | Criar nova lista |
| PUT | `/dspace-forms-editor/value-pairs/{id}` | Atualizar lista |
| DELETE | `/dspace-forms-editor/value-pairs/{id}` | Excluir lista |
| POST | `/dspace-forms-editor/value-pairs/{id}/items` | Adicionar item |
| DELETE | `/dspace-forms-editor/value-pairs/{id}/items/{item}` | Remover item |
| POST | `/dspace-forms-editor/value-pairs/{id}/items/move` | Reordenar itens |

### Mapeamentos de Campos

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/dspace-forms-editor/form-maps` | Listar mapeamentos |
| POST | `/dspace-forms-editor/form-maps` | Criar mapeamento |
| PUT | `/dspace-forms-editor/form-maps/{id}` | Atualizar mapeamento |
| DELETE | `/dspace-forms-editor/form-maps/{id}` | Excluir mapeamento |

### Formulários

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/dspace-forms-editor/forms` | Listar formulários da config ativa |
| POST | `/dspace-forms-editor/forms` | Criar formulário |
| GET | `/dspace-forms-editor/forms/{id}/edit` | Editar formulário |
| PUT | `/dspace-forms-editor/forms/{id}` | Atualizar formulário |
| DELETE | `/dspace-forms-editor/forms/{id}` | Excluir formulário |
| POST | `/dspace-forms-editor/forms/{id}/rows` | Adicionar linha |
| DELETE | `/dspace-forms-editor/rows/{id}` | Remover linha |
| POST | `/dspace-forms-editor/rows/{id}/fields` | Adicionar campo |
| PUT | `/dspace-forms-editor/fields/{id}` | Atualizar campo |
| DELETE | `/dspace-forms-editor/fields/{id}` | Remover campo |

### Templates de Email

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/dspace-forms-editor/emails` | Listar templates |
| POST | `/dspace-forms-editor/emails` | Criar template |
| PUT | `/dspace-forms-editor/emails/{id}` | Atualizar template |
| DELETE | `/dspace-forms-editor/emails/{id}` | Excluir template |

---

## 4. Controllers

### `DspaceFormsController`
Controller principal. Gerencia a tela inicial (lista de configurações) e a exportação ZIP de toda a configuração selecionada.

### `DspaceConfigSession`
Gerencia qual configuração XML está ativa na sessão do usuário. Outros controllers dependem desta sessão para saber o contexto.

### `DspaceFormController`
CRUD de formulários vinculados à configuração ativa na sessão.

### `DspaceFormFieldController`
Gerencia campos individuais dentro das linhas dos formulários. Campos têm tipo, label, obrigatoriedade e ordenação.

### `DspaceFormRowController`
Gerencia linhas do formulário (contêineres de campos).

### `DspaceValuePairsListController`
CRUD de listas de valores (dropdowns) reutilizáveis nos campos de formulário. Suporta adição, remoção e reordenação de itens.

### `DspaceFormMapController`
Define o mapeamento entre campos do formulário e propriedades/metadados do DSpace.

### `DspaceEmailController`
CRUD de templates de email usados nas notificações do fluxo de submissão DSpace.

---

## 5. Models

### `DspaceXmlConfiguration`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` (criador) |
| `name` | string | Nome da configuração |
| `description` | text | Descrição |

### `DspaceForm`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `xml_configuration_id` | integer | FK para configuração |
| `nome` | string | Nome do formulário |
| `descricao` | text | Descrição |

### `DspaceFormRow`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `form_id` | integer | FK para `DspaceForm` |
| `ordem` | integer | Posição na sequência |

### `DspaceFormField`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `row_id` | integer | FK para `DspaceFormRow` |
| `tipo` | string | Tipo do campo (text, textarea, dropdown, etc.) |
| `label` | string | Rótulo exibido |
| `obrigatorio` | boolean | Campo obrigatório |
| `repeatable` | boolean | Permite múltiplos valores |
| `ordem` | integer | Posição na linha |

### `DspaceValuePairsList`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `label` | string | Nome da lista dropdown |

### `DspaceValuePair`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `list_id` | integer | FK para `DspaceValuePairsList` |
| `stored_value` | string | Valor interno armazenado |
| `displayed_value` | string | Valor exibido ao usuário |
| `ordem` | integer | Posição na lista |

### `DspaceFormMap`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `form_id` | integer | FK para `DspaceForm` |
| `campo_dspace_id` | string | Propriedade de metadado DSpace |
| `field_id` | integer | FK para `DspaceFormField` |

---

## 6. Fluxo Principal

```
Selecionar/criar configuração → Configuração ativa vai para sessão
→ Criar formulários com linhas e campos
→ Criar listas de valores para campos dropdown
→ Mapear campos para propriedades DSpace
→ Configurar templates de email
→ Exportar ZIP
```

---

## 7. Notas para Agentes de IA

- A **sessão de configuração ativa** é central: a maioria das operações opera sobre a configuração carregada na sessão via `DspaceConfigSession`.
- O módulo possui componentes **Livewire** (legado), o que é uma exceção à regra geral do projeto.
- As migrations estão dentro do próprio módulo em `database/migrations/`, não na pasta global.
- O middleware `RegistrarAcesso:DspaceForms` rastreia acessos para o módulo Métricas.
