# Módulo EnvioWhatsapp — Guia para Desenvolvedores e Agentes de IA

## 1. Propósito

O módulo **EnvioWhatsapp** é uma ferramenta de envio em massa de mensagens WhatsApp. O usuário carrega um arquivo com a lista de contatos, configura a mensagem e processa o envio em lote.

**Fluxo:** Wizard de 3 etapas (Upload → Preview/Configuração → Processamento).

**Acesso:** Autenticado (`auth`).  
**Middleware:** `RegistrarAcesso:EnvioWhatsapp`

---

## 2. Estrutura de Diretórios

```
app/Modules/EnvioWhatsapp/
├── Http/
│   └── Controllers/
│       └── WhatsappController.php     # Controller único (wizard 3 etapas)
├── resources/
│   └── views/
│       ├── index.blade.php            # Página inicial
│       ├── step1.blade.php            # Etapa 1: Upload do arquivo
│       ├── step2.blade.php            # Etapa 2: Preview e configuração
│       ├── step3.blade.php            # Etapa 3: Processamento e status
│       └── components/
│           └── menu-main.blade.php    # Menu do módulo
├── EnvioWhatsappServiceProvider.php
└── routes.php
```

---

## 3. Rotas

**Prefixo:** `/ferramentas/whatsapp`  
**Middleware:** `web`, `RegistrarAcesso:EnvioWhatsapp`  
**Nome base:** `whatsapp.*`

| Método | URI | Nome | Descrição |
|--------|-----|------|-----------|
| GET | `/ferramentas/whatsapp` | `whatsapp.index` | Página inicial (Etapa 1) |
| POST | `/ferramentas/whatsapp/upload` | `whatsapp.upload` | Processar upload (Etapa 2) |
| POST | `/ferramentas/whatsapp/processar` | `whatsapp.processar` | Enviar mensagens (Etapa 3) |

---

## 4. Controller

### `WhatsappController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `step1()` | GET `/ferramentas/whatsapp` | Exibe formulário de upload |
| `step2()` | POST `/ferramentas/whatsapp/upload` | Recebe arquivo, valida, exibe preview dos contatos e configuração da mensagem |
| `step3()` | POST `/ferramentas/whatsapp/processar` | Processa o envio das mensagens em lote |

---

## 5. Views

| Arquivo | Etapa | Descrição |
|---------|-------|-----------|
| `step1.blade.php` | 1 | Formulário de upload do arquivo de contatos |
| `step2.blade.php` | 2 | Preview da lista de contatos e campo para personalizar a mensagem |
| `step3.blade.php` | 3 | Exibe status do processamento e resultado do envio |
| `index.blade.php` | — | Página inicial/redirect para step1 |

---

## 6. Fluxo Principal

```
Usuário acessa /ferramentas/whatsapp (Etapa 1)
→ Faz upload do arquivo com lista de contatos
→ Sistema exibe preview dos contatos e configuração da mensagem (Etapa 2)
→ Usuário confirma e dispara o envio
→ Sistema processa e exibe resultado (Etapa 3)
```

---

## 7. Notas para Agentes de IA

- Este é um dos módulos mais simples: um único controller, sem models próprios.
- Não há persistência em banco de dados — o fluxo é stateless (baseado em sessão/formulários).
- O middleware `RegistrarAcesso:EnvioWhatsapp` rastreia acessos para as métricas do sistema.
- A lógica de integração real com a API do WhatsApp está dentro do `WhatsappController@step3`.
- Para expandir o módulo, considere adicionar models para histórico de envios e rastreamento de status de entrega.
