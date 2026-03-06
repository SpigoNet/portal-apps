# Módulo ANT (Academic Notification Tool) — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **ANT** é um sistema de gerenciamento acadêmico para:

- Gestão de **matérias** e **professores**
- Criação e entrega de **trabalhos** (individuais ou em grupo)
- **Provas** com questões de múltipla escolha
- **Correção** de trabalhos (manual + sugestões via IA)
- Upload de **materiais** de apoio
- Configuração de **pesos** de notas
- **Importação de alunos** via CSV/Excel
- **Boletim** com notas dos alunos

**Acesso:** Autenticado (`auth`). Três perfis: **Aluno**, **Professor**, **Admin do App**.  
**Middleware:** `RegistrarAcesso:ANT`

---

## 2. Estrutura de Diretórios

```
app/Modules/ANT/
├── Http/
│   └── Controllers/
│       ├── AdminAlunoController.php      # Admin: CRUD de alunos (importação CSV)
│       ├── AdminMateriaController.php    # Admin: CRUD de matérias
│       ├── AdminProfessorController.php  # Admin: Associar professores a matérias
│       ├── AntAdminController.php        # Admin: Dashboard geral
│       ├── AntHomeController.php         # Aluno: Home, vinculação de RA
│       ├── CorrecaoController.php        # Professor: Correção + sugestão por IA
│       ├── MaterialController.php        # Professor/Admin: Upload de materiais
│       ├── PesoController.php            # Professor: Configuração de pesos
│       ├── ProfessorController.php       # Professor: Dashboard e criação de trabalhos
│       ├── ProvaController.php           # Aluno/Professor: Provas e resultados
│       └── TrabalhoController.php        # Aluno: Entrega de trabalho, formação de grupos
├── Models/
│   ├── AntAluno.php                     # Aluno (RA, nome, email)
│   ├── AntAlternativa.php               # Alternativa de questão de prova
│   ├── AntConfiguracao.php              # Configurações do módulo
│   ├── AntEntrega.php                   # Entrega de trabalho pelo aluno
│   ├── AntLink.php                      # Links de recursos
│   ├── AntMaterial.php                  # Material de apoio
│   ├── AntMateria.php                   # Matéria/disciplina
│   ├── AntPeso.php                      # Regra de peso de nota
│   ├── AntProva.php                     # Prova/exame
│   ├── AntProvaResposta.php             # Resposta do aluno à prova
│   ├── AntQuestao.php                   # Questão de prova
│   ├── AntTipoTrabalho.php              # Enum de tipos de trabalho
│   └── AntTrabalho.php                  # Trabalho/atividade
├── resources/
│   └── views/
│       ├── admin/                        # Dashboards administrativos
│       ├── aluno/                        # Views do aluno
│       ├── correcao/                     # Interface de correção
│       ├── materiais/                    # CRUD de materiais
│       ├── professores/                  # Dashboard do professor
│       ├── provas/                       # Resultados de provas
│       ├── trabalhos/                    # Entrega de trabalhos
│       └── components/                   # Componentes de layout
├── estrturaBD.sql                        # Schema do banco de dados
├── ANTServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/ant`  
**Middleware base:** `web`, `auth`, `RegistrarAcesso:ANT`  
**Nome base das rotas:** `ant.*`

### Rotas do Aluno

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/ant` | Home do aluno (lista matérias/trabalhos) |
| GET/POST | `/ant/vincular-ra` | Vincular RA (número de matrícula) |
| GET | `/ant/trabalho/{id}` | Ver trabalho e enviar entrega |
| POST | `/ant/trabalho/{id}/entregar` | Submeter entrega |
| GET | `/ant/boletim/{id}` | Ver notas da matéria |
| GET | `/ant/prova/{id}/resultado` | Resultado da prova |
| GET | `/ant/correcao/{id}` | Ver feedback da correção |
| GET | `/ant/materia/{id}/materiais` | Listar materiais da matéria |
| GET | `/ant/buscar-aluno` | Buscar aluno por RA (AJAX, formação de grupo) |

### Rotas do Professor

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/ant/professor` | Dashboard do professor |
| GET/POST | `/ant/professor/novo-trabalho` | Criar novo trabalho |
| GET | `/ant/professor/trabalho/{id}` | Ver entregas dos alunos |
| GET | `/ant/professor/materia/{id}/boletim` | Boletim completo da matéria |
| GET/POST | `/ant/professor/pesos` | Configurar pesos de nota |
| GET/POST | `/ant/professor/materia/{id}/materiais` | Gerenciar materiais |
| GET/POST | `/ant/professor/correcao/{id}` | Corrigir entrega + IA |

### Rotas Administrativas

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/ant/admin` | Dashboard administrativo |
| GET/POST/PUT/DELETE | `/ant/admin/materias` | CRUD de matérias |
| GET/POST/PUT/DELETE | `/ant/admin/professores` | CRUD/vinculação de professores |
| GET/POST/DELETE | `/ant/admin/alunos` | Listar/importar/remover alunos |

---

## 4. Controllers

### `AntHomeController`
- Exibe a home do aluno com suas matérias e trabalhos ativos
- Gerencia a vinculação de RA (Registro Acadêmico) ao usuário

### `TrabalhoController`
- Aluno visualiza o trabalho e faz upload da entrega
- Busca de colegas de grupo via AJAX (`/ant/buscar-aluno`)
- Gerencia formação de grupos (máx. de alunos configurável)

### `ProfessorController`
- Dashboard com matérias e trabalhos do professor
- Criação de novos trabalhos com configurações (tipo, prazo, máximo de integrantes)
- Visualização de entregas por trabalho

### `CorrecaoController`
- Interface de correção de entregas (PDF, imagens, texto)
- Integração com IA para sugestões de correção
- Salva nota e feedback da entrega

### `AdminMateriaController`
- CRUD de matérias (nome, código)
- Associação de professores às matérias

### `AdminAlunoController`
- Listagem de alunos matriculados
- Importação em lote via arquivo CSV/Excel
- Exclusão de alunos

### `MaterialController`
- Upload de materiais de apoio (PDFs, apresentações, etc.)
- Organização por matéria

### `PesoController`
- Define regras de ponderação de notas por tipo de trabalho

### `ProvaController`
- Exibe resultados de provas com pontuação calculada

---

## 5. Models

### `AntTrabalho`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `materia_id` | integer | FK para `AntMateria` |
| `tipo_trabalho_id` | integer | FK para `AntTipoTrabalho` |
| `peso_id` | integer | FK para `AntPeso` |
| `nome` | string | Nome do trabalho |
| `descricao` | text | Enunciado |
| `dicas_correcao` | text | Critérios de correção (usados pela IA) |
| `prazo` | datetime | Data limite de entrega |
| `semestre` | string | Ex: "2026.1" |
| `maximo_alunos` | integer | Máximo de integrantes no grupo |

### `AntEntrega`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `trabalho_id` | integer | FK para `AntTrabalho` |
| `aluno_id` | integer | FK para `AntAluno` |
| `arquivo_enviado` | string | Caminho do arquivo |
| `data_entrega` | datetime | Timestamp da entrega |
| `nota` | decimal | Nota atribuída (0-10) |
| `feedback` | text | Comentário do professor |

### `AntAluno`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `ra` | string | Registro Acadêmico (matrícula) |
| `nome` | string | Nome completo |
| `email` | string | Email do aluno |
| `user_id` | integer | FK para `User` (quando vinculado) |

### `AntMateria`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `nome` | string | Nome da matéria |
| `codigo` | string | Código da disciplina |

### `AntProva`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | Chave primária |
| `materia_id` | integer | FK para `AntMateria` |
| `titulo` | string | Título da prova |
| `data_aplicacao` | date | Data de aplicação |

### `AntQuestao` / `AntAlternativa`
Questões de múltipla escolha com alternativas. `AntProvaResposta` registra as escolhas dos alunos.

---

## 6. Fluxo Principal

```
Admin cria matéria → Admin associa professor → Professor cria trabalho
→ Aluno vincula RA → Aluno entrega trabalho → Professor corrige (+ IA sugere nota)
→ Aluno vê nota no boletim
```

---

## 7. Integração com IA

O `CorrecaoController` usa o serviço de IA configurado no módulo Admin para gerar sugestões de correção. O campo `dicas_correcao` do `AntTrabalho` serve como instrução ao modelo de IA.

---

## 8. Controle de Acesso

- **Aluno:** Vê somente suas matérias e entregas
- **Professor:** Acessa rotas `/ant/professor/*`, vê somente suas matérias
- **Admin do App:** Acessa rotas `/ant/admin/*`, controle total. Verificado via Gate `admin-do-app` (portal_app_id: 4)

---

## 9. Notas para Agentes de IA

- O arquivo `estrturaBD.sql` na raiz do módulo contém o schema completo do banco de dados.
- O módulo usa `RegistrarAcesso:ANT` para rastrear acessos nas métricas do sistema.
- Grupos de trabalho são formados por busca AJAX de colegas pelo RA.
- A interface de correção suporta renderização de diferentes tipos de arquivo (PDF, imagem, texto).
