# Módulo TreeTask — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **TreeTask** é um sistema completo de gerenciamento de projetos e tarefas com:

- Hierarquia **Projeto → Fase → Tarefa**
- **Drag-and-drop** para reordenação em todos os níveis
- **Anexos** de arquivos nas tarefas
- Integração com **IA** para geração automática de tarefas
- **Gamificação** com mensagens motivacionais e celebrações
- **Modo Foco** (zen mode) para trabalho sem distrações
- Visualização em **árvore** de todos os projetos
- **API REST** completa com autenticação por token (para integrações externas como Alfred)
- **Relatório matinal** diário com prioridades do dia

**Acesso:** Interface web autenticada. API via token MD5.  
**Middleware web:** `RegistrarAcesso:TreeTask`

---

## 2. Estrutura de Diretórios

```
app/Modules/TreeTask/
├── Http/
│   ├── Controllers/
│   │   ├── AiCommandController.php      # Geração de tarefas via IA
│   │   ├── AnexoController.php          # Upload/download de arquivos
│   │   ├── ApiController.php            # API REST completa (40+ endpoints)
│   │   ├── CelebrationController.php    # Tela de celebração de conclusão
│   │   ├── FaseController.php           # CRUD de fases
│   │   ├── FocusController.php          # Modo foco/zen
│   │   ├── GamificationController.php   # Mensagens motivacionais
│   │   ├── GoodMorningController.php    # Relatório matinal
│   │   ├── OrderController.php          # Reordenação drag-and-drop
│   │   ├── ProjetoController.php        # CRUD de projetos
│   │   └── TarefaController.php         # CRUD de tarefas
│   └── Middleware/
│       └── TokenAuth.php                # Autenticação por token (API)
├── Models/
│   ├── Anexo.php                        # Arquivo anexado a tarefa
│   ├── Fase.php                         # Fase/etapa do projeto
│   ├── GamificationReward.php           # Registro de conquistas
│   ├── LorePrompt.php                   # Prompts narrativos da gamificação
│   ├── Projeto.php                      # Projeto (container principal)
│   ├── Tarefa.php                       # Tarefa individual
│   ├── UserAvatar.php                   # Avatar do usuário
│   └── UserSetting.php                  # Configurações do usuário
├── database/
│   └── migrations/                      # Migrations do módulo
├── resources/
│   └── views/
│       ├── index.blade.php              # Lista de projetos
│       ├── show.blade.php               # Projeto com fases e tarefas (kanban)
│       ├── create.blade.php             # Criar projeto
│       ├── tree.blade.php               # Visualização em árvore
│       ├── good_morning.blade.php       # Relatório matinal
│       ├── tarefas/
│       │   ├── create.blade.php         # Criar tarefa
│       │   ├── edit.blade.php           # Editar tarefa
│       │   └── show.blade.php           # Detalhes da tarefa
│       ├── focus/                       # Modo foco
│       ├── ai_command/                  # Interface de comando IA
│       ├── celebration/
│       │   └── show.blade.php           # Animação de conclusão
│       └── components/                  # Componentes de layout
├── TreeTaskServiceProvider.php
├── routes.php
└── API_DOCUMENTATION.md                 # Documentação completa da API REST
```

---

## 3. Rotas Web

**Prefixo:** `/treetask`  
**Middleware:** `web`, `auth`, `RegistrarAcesso:TreeTask`  
**Nome base:** `treetask.*`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/treetask` | `treetask.index` | Lista projetos do usuário |
| GET | `/treetask/criar` | `treetask.create` | Formulário de novo projeto |
| POST | `/treetask` | `treetask.store` | Salvar projeto |
| GET | `/treetask/bom-dia` | `treetask.bom-dia` | Relatório matinal |
| GET | `/treetask/projeto/{id}` | `treetask.projeto.show` | Projeto com kanban |
| GET | `/treetask/projeto/{id}/arvore` | `treetask.projeto.arvore` | Visualização em árvore |
| POST | `/treetask/fases` | `treetask.fases.store` | Criar fase |
| DELETE | `/treetask/fases/{id}` | `treetask.fases.destroy` | Excluir fase |
| GET | `/treetask/fases/{id}/tarefa/criar` | `treetask.tarefas.create` | Formulário de tarefa |
| POST | `/treetask/tarefas` | `treetask.tarefas.store` | Criar tarefa |
| GET | `/treetask/tarefas/{id}` | `treetask.tarefas.show` | Detalhes da tarefa |
| GET | `/treetask/tarefas/{id}/editar` | `treetask.tarefas.edit` | Editar tarefa |
| PUT | `/treetask/tarefas/{id}` | `treetask.tarefas.update` | Atualizar tarefa |
| PATCH | `/treetask/tarefas/{id}/status` | `treetask.tarefas.status` | Atualizar somente status |
| POST | `/treetask/tarefas/{id}/anexos` | `treetask.anexos.store` | Upload de arquivo |
| GET | `/treetask/anexos/{id}/download` | `treetask.anexos.download` | Download de arquivo |
| DELETE | `/treetask/tarefas/{id}/anexos/{anexo_id}` | `treetask.anexos.destroy` | Excluir anexo |
| GET | `/treetask/meu-foco` | `treetask.foco` | Modo foco |
| GET | `/treetask/gamificacao/motivacao` | `treetask.motivacao` | Mensagem motivacional |
| POST | `/treetask/reorder/fases` | `treetask.reorder.fases` | Reordenar fases (AJAX) |
| POST | `/treetask/reorder/tarefas` | `treetask.reorder.tarefas` | Reordenar tarefas (AJAX) |
| POST | `/treetask/reorder/global` | `treetask.reorder.global` | Reordenação global |
| GET | `/treetask/tarefas/{id}/comemorar` | `treetask.comemorar` | Tela de celebração |
| GET | `/treetask/forcar-envio-diario` | — | Força execução do cron |
| GET | `/treetask/ia-comando` | `treetask.ia-comando` | Interface de IA |
| POST | `/treetask/ia-comando/preview` | `treetask.ia-comando.preview` | Preview geração IA |
| POST | `/treetask/ia-comando/executar` | `treetask.ia-comando.executar` | Executar comando IA |

---

## 4. API REST

**Prefixo:** `/treetask/api/v1`  
**Middleware:** `TokenAuth` (exceto `/health`)  
**Documentação completa:** `API_DOCUMENTATION.md`

### Autenticação da API

```http
X-User-ID: 1
X-Token: md5(email + password_hash)
```

### Endpoints Principais

| Recurso | Endpoints |
|---------|-----------|
| Health | GET `/health` (público) |
| Projetos | GET/POST `/projetos`, GET/PUT/DELETE `/projetos/{id}` |
| Fases | GET `/projetos/{id}/fases`, POST `/fases`, GET/PUT/DELETE `/fases/{id}` |
| Tarefas | GET `/fases/{id}/tarefas`, POST `/tarefas`, GET/PUT/DELETE `/tarefas/{id}` |
| Status Tarefa | PATCH `/tarefas/{id}/status` |
| Anexos | GET/POST `/tarefas/{id}/anexos`, GET/DELETE `/anexos/{id}`, GET `/anexos/{id}/download` |
| Listagem Avançada | GET `/tarefas` (com filtros) |
| Relatório Manhã | GET `/tarefas/relatorio/manha` |
| Tarefas Paradas | GET `/tarefas/paradas` |
| Tarefa Completa | GET `/tarefas/{id}/completa` |

---

## 5. Controllers

### Web

#### `ProjetoController`
CRUD de projetos. Exibe a view kanban com fases e tarefas. Também gerencia a visualização em árvore.

#### `TarefaController`
CRUD completo de tarefas. Atualização parcial de status. Upload e download de anexos.

#### `FaseController`
Criação e exclusão de fases dentro de projetos.

#### `AnexoController`
Gerenciamento de arquivos anexados às tarefas (upload, download, exclusão).

#### `OrderController`
Recebe chamadas AJAX para reordenar fases e tarefas (drag-and-drop). Atualiza o campo `ordem` de cada registro.

#### `GoodMorningController`
Gera o relatório matinal com as tarefas prioritárias do dia.

#### `FocusController`
Exibe o modo foco: interface minimalista para trabalhar em uma tarefa específica sem distrações.

#### `GamificationController`
Retorna mensagens motivacionais e recompensas de gamificação.

#### `CelebrationController`
Exibe animação de celebração quando uma tarefa é concluída.

#### `AiCommandController`
Interface para geração automática de tarefas usando IA. Preview antes de executar para confirmação do usuário.

### API

#### `ApiController`
Implementa todos os ~40 endpoints da API REST. Usa o middleware `TokenAuth` para autenticação.

---

## 6. Models

### `Projeto`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_projeto` | integer | Chave primária |
| `id_user_owner` | integer | FK para `User` |
| `nome` | string | Nome do projeto |
| `descricao` | text | Descrição |
| `status` | string | `Planejamento`, `Em Andamento`, `Concluído` |
| `data_inicio` | date | Data de início |
| `data_prevista_termino` | date | Prazo previsto |
| `data_conclusao_real` | date | Data real de conclusão |

### `Fase`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_fase` | integer | Chave primária |
| `id_projeto` | integer | FK para `Projeto` |
| `nome` | string | Nome (ex: "A Fazer", "Em Andamento") |
| `descricao` | text | Descrição opcional |
| `ordem` | integer | Posição no kanban |
| `status` | string | Status da fase |

### `Tarefa`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_tarefa` | integer | Chave primária |
| `id_fase` | integer | FK para `Fase` |
| `titulo` | string | Título da tarefa |
| `descricao` | text | Descrição detalhada |
| `status` | string | `A Fazer`, `Em Andamento`, `Concluído`, `Planejamento`, `Aguardando resposta` |
| `prioridade` | string | `Baixa`, `Média`, `Alta`, `Urgente` |
| `id_user_responsavel` | integer | FK para `User` |
| `ordem` | integer | Posição na fase |
| `data_vencimento` | date | Prazo da tarefa |
| `estimativa_tempo` | decimal | Horas estimadas |

### `Anexo`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id_anexo` | integer | Chave primária |
| `id_tarefa` | integer | FK para `Tarefa` |
| `id_user_upload` | integer | FK para `User` |
| `nome_arquivo` | string | Nome original |
| `path_arquivo` | string | Caminho no storage |
| `mime_type` | string | Tipo MIME |
| `tamanho` | integer | Tamanho em bytes |

### `GamificationReward`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `user_id` | integer | FK para `User` |
| `tipo` | string | Tipo de conquista |
| `descricao` | text | Descrição da conquista |
| `data_conquista` | date | Quando foi obtida |

### `UserSetting`
Pares chave-valor de preferências do usuário (ex: tema, notificações).

### `UserAvatar`
Configuração do avatar do usuário na interface gamificada.

### `LorePrompt`
Prompts narrativos utilizados para gerar mensagens motivacionais temáticas.

---

## 7. Mapeamento de Status e Prioridade

### Status

| Status | Código API | Ordem |
|--------|-----------|-------|
| `A Fazer` | `pendente` | 1 |
| `Planejamento` | `pendente` | 2 |
| `Em Andamento` | `andamento` | 3 |
| `Aguardando resposta` | `andamento` | 4 |
| `Concluído` | `concluida` | 5 |

### Prioridade

| Prioridade | Valor | Ordem |
|-----------|-------|-------|
| `Urgente` | 4 | Maior |
| `Alta` | 3 | |
| `Média` | 2 | |
| `Baixa` | 1 | Menor |

---

## 8. Autenticação da API

O middleware `TokenAuth` valida:

```php
// Gerar token
$token = md5($user->email . $user->password);

// Headers da requisição
X-User-ID: {id_do_usuario}
X-Token: {token_md5}
```

O token permanece válido enquanto a senha do usuário não for alterada.

---

## 9. Notas para Agentes de IA

- As chaves primárias dos models têm prefixo: `id_projeto`, `id_fase`, `id_tarefa`, `id_anexo` (diferente do padrão Laravel `id`).
- Ao mover uma tarefa de fase, altere o campo `id_fase` via `PUT /tarefas/{id}`.
- A API REST está completamente documentada em `API_DOCUMENTATION.md` na raiz do módulo.
- O middleware `RegistrarAcesso:TreeTask` só é aplicado nas rotas **web**, não nas rotas de API.
- Para integrar com o assistente Alfred, utilize os endpoints de integração: `/tarefas`, `/tarefas/relatorio/manha`, `/tarefas/paradas`, `/tarefas/{id}/completa`.
- As migrations ficam dentro do módulo em `database/migrations/`.
- Drag-and-drop funciona via chamadas AJAX para os endpoints `/reorder/*`.
