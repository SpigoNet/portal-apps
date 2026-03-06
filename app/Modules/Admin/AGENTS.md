# Módulo Admin — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **Admin** é o painel de administração central do portal. Ele permite gerenciar:

- **Aplicativos** do portal (criar, editar, definir visibilidade e usuários com acesso)
- **Usuários** do sistema (CRUD global)
- **Pacotes** (features packages)
- **Provedores e modelos de IA** (configurar chaves de API e habilitar/desabilitar modelos)
- **Logs do sistema** (visualizar, baixar e limpar logs em tempo real)
- **Gerador de ícones** para apps do portal

**Acesso:** Somente usuários com role `admin` (`middleware: EnsureUserIsAdmin`).

---

## 2. Estrutura de Diretórios

```
app/Modules/Admin/
├── Http/
│   └── Controllers/
│       ├── AIModeloController.php       # Gerenciamento de modelos de IA
│       ├── AIProvedorController.php     # Configuração de provedores de IA
│       ├── AppManagerController.php     # CRUD de aplicativos do portal
│       ├── AppUserManagerController.php # Associação usuário ↔ app
│       ├── IconGeneratorController.php  # Ferramenta de geração de ícones
│       ├── LogViewerController.php      # Visualizador de logs
│       ├── PackageManagerController.php # Gerenciamento de pacotes
│       └── UserManagerController.php    # CRUD de usuários
├── Models/
│   ├── AIModelo.php                    # Modelo de IA (ex: gpt-4o, claude-3-5-sonnet)
│   ├── AIModeloPadrao.php              # Modelo padrão por contexto
│   └── AIProvedor.php                  # Provedor de IA (ex: OpenAI, Anthropic)
├── Services/                           # Serviços auxiliares
├── resources/
│   └── views/
│       ├── ai/                         # Gerenciamento de provedores e modelos
│       ├── apps/                       # CRUD de apps e associação de usuários
│       ├── admin/logs/                 # Visualizador de logs
│       ├── icon-generator/             # Ferramenta de ícones
│       ├── packages/                   # Gerenciamento de pacotes
│       ├── users/                      # CRUD de usuários
│       └── components/                 # Componentes de layout
├── AdminServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/admin`  
**Middleware:** `EnsureUserIsAdmin`  
**Nome base das rotas:** `admin.*`

| Método | URI | Nome | Controller@método | Descrição |
|--------|-----|------|-------------------|-----------|
| GET | `/admin` | `admin.index` | redirect | Redireciona para apps |
| GET/POST | `/admin/icon-generator` | `admin.icon-generator.*` | `IconGeneratorController` | Ferramenta de ícones |
| GET | `/admin/apps` | `admin.apps.index` | `AppManagerController@index` | Lista de apps |
| GET | `/admin/apps/create` | `admin.apps.create` | `AppManagerController@create` | Formulário de criação |
| POST | `/admin/apps` | `admin.apps.store` | `AppManagerController@store` | Salvar novo app |
| GET | `/admin/apps/{app}/edit` | `admin.apps.edit` | `AppManagerController@edit` | Editar app |
| PUT | `/admin/apps/{app}` | `admin.apps.update` | `AppManagerController@update` | Atualizar app |
| DELETE | `/admin/apps/{app}` | `admin.apps.destroy` | `AppManagerController@destroy` | Excluir app |
| GET | `/admin/apps/{app}/users` | `admin.apps.users.index` | `AppUserManagerController@index` | Usuários do app |
| POST | `/admin/apps/{app}/users` | `admin.apps.users.store` | `AppUserManagerController@store` | Adicionar usuário ao app |
| PUT | `/admin/apps/{app}/users/{user}` | `admin.apps.users.update` | `AppUserManagerController@update` | Atualizar acesso |
| DELETE | `/admin/apps/{app}/users/{user}` | `admin.apps.users.destroy` | `AppUserManagerController@destroy` | Remover acesso |
| GET | `/admin/users` | `admin.users.index` | `UserManagerController@index` | Lista de usuários |
| POST | `/admin/users` | `admin.users.store` | `UserManagerController@store` | Criar usuário |
| PUT | `/admin/users/{user}` | `admin.users.update` | `UserManagerController@update` | Atualizar usuário |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `UserManagerController@destroy` | Excluir usuário |
| GET | `/admin/packages` | `admin.packages.*` | `PackageManagerController` | Gestão de pacotes |
| GET | `/admin/ai/provedores` | `admin.ai.provedores.index` | `AIProvedorController@index` | Lista de provedores |
| POST | `/admin/ai/provedores` | `admin.ai.provedores.store` | `AIProvedorController@store` | Criar provedor |
| PUT | `/admin/ai/provedores/{id}` | `admin.ai.provedores.update` | `AIProvedorController@update` | Atualizar provedor |
| POST | `/admin/ai/provedores/{id}/sync` | `admin.ai.provedores.sync` | `AIProvedorController@sync` | Sincronizar modelos |
| GET | `/admin/ai/provedores/{id}/modelos` | `admin.ai.provedores.modelos` | `AIProvedorController@modelos` | Ver modelos do provedor |
| POST | `/admin/ai/modelos/{id}/toggle` | `admin.ai.modelos.toggle` | `AIModeloController@toggle` | Ativar/desativar modelo |
| POST | `/admin/ai/modelos/{id}/set-default` | `admin.ai.modelos.setDefault` | `AIModeloController@setDefault` | Definir modelo padrão |
| GET | `/admin/logs` | `admin.logs.index` | `LogViewerController@index` | Visualizador de logs |
| GET | `/admin/logs/tail` | `admin.logs.tail` | `LogViewerController@tail` | Stream em tempo real |
| POST | `/admin/logs/clear` | `admin.logs.clear` | `LogViewerController@clear` | Limpar logs |
| GET | `/admin/logs/download` | `admin.logs.download` | `LogViewerController@download` | Baixar log |

---

## 4. Controllers

### `AppManagerController`
Gerencia os aplicativos registrados no portal (`PortalApp`). Controla título, descrição, ícone, link de acesso e visibilidade (pública, privada ou restrita a usuários específicos).

### `AppUserManagerController`
Associa usuários a aplicativos específicos. Usado quando a visibilidade do app é restrita.

### `UserManagerController`
CRUD global de usuários do sistema (`User`). Permite criar, editar e excluir contas.

### `AIProvedorController`
Configura provedores de IA (ex: OpenAI, Anthropic, Google). Armazena a chave de API e sincroniza a lista de modelos disponíveis do provedor.

### `AIModeloController`
Gerencia modelos individuais de IA: ativar/desativar e definir qual é o padrão para uso nos módulos.

### `LogViewerController`
Lê e exibe logs do Laravel em tempo real. Suporta streaming via `tail`, download do arquivo e limpeza.

### `IconGeneratorController`
Ferramenta para gerar ícones de apps do portal.

### `PackageManagerController`
Gerenciamento de pacotes de funcionalidades do sistema.

---

## 5. Models

### `AIProvedor`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `nome` | string | Nome do provedor (ex: "OpenAI") |
| `api_key` | string | Chave de API (criptografada) |
| `configuracao_json` | json | Configurações adicionais |
| `is_ativo` | boolean | Provedor habilitado |

### `AIModelo`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `provedor_id` | integer | FK para `AIProvedor` |
| `nome` | string | Nome amigável |
| `modelo_externo_id` | string | ID do modelo na API do provedor |
| `is_ativo` | boolean | Modelo habilitado |
| `is_padrao` | boolean | Modelo padrão do sistema |

### `AIModeloPadrao`
Registra qual modelo é o padrão para cada contexto de uso.

---

## 6. Como Adicionar um Novo App ao Portal

1. Acesse `/admin/apps/create`
2. Preencha: título, descrição, link de início, ícone, visibilidade
3. Se visibilidade for restrita, associe usuários em `/admin/apps/{id}/users`
4. O app aparecerá na página inicial do portal para os usuários com acesso

---

## 7. Como Configurar um Provedor de IA

1. Acesse `/admin/ai/provedores`
2. Adicione um novo provedor com nome e chave de API
3. Clique em **Sincronizar** para buscar os modelos disponíveis
4. Em `/admin/ai/provedores/{id}/modelos`, ative os modelos desejados
5. Defina um modelo como **padrão** para uso global

---

## 8. Notas para Agentes de IA

- Para verificar quais módulos estão registrados, consulte `bootstrap/providers.php`.
- A tabela `portal_apps` (model `PortalApp` em `app/Models/`) define o que aparece no menu do portal.
- Os models `AIProvedor` e `AIModelo` deste módulo são utilizados por outros módulos (ex: MundosDeMim, TreeTask) para geração de conteúdo com IA.
- O middleware `EnsureUserIsAdmin` está em `app/Http/Middleware/EnsureUserIsAdmin.php`.
