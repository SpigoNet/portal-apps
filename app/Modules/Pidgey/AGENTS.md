# Pidgey Module — Agent Guidelines

## Purpose

Message dispatcher. Receives API requests and delivers messages through one of
several channels (Telegram, WhatsApp, Email — more to come). Acts as the central
"send" hub so other modules emit a single request instead of owning channel logic.

Also provides **web screens** where users schedule recurring/one-off messages
that are delivered by their Alfred `Persona` (Telegram) on a schedule.

- **API Prefix:** `/api/pidgey` | **Auth:** none yet | **Analytics:** none yet
- **Web Prefix:** `/pidgey` | **Auth:** `web` + `auth` + `RegistrarAcesso:Pidgey`
- **PortalApp id:** `12` (`config('pidgey.portal_app_id')`) — registrado em `PortalSeeder`.

## Web (Telas de Agendamento)

- **Rotas:** `app/Modules/Pidgey/routes/web.php` (agrupadas em `pidgey.*`).
- **Controller:** `App\Modules\Pidgey\Http\Controllers\AgendamentoController`
  (index/store/destroy/toggle).
- **Model:** `App\Modules\Pidgey\Models\Agendamento` (tabela `pidgey_agendamentos`).
  Possui global scope por `user_id` (Auth) e helpers `deveEnviarAgora()`,
  `calcularProximaExecucao()` e `getFrequenciaLabelAttribute()`.
- **Views:** `resources/views/agendamentos/index.blade.php` + `components/layout.blade.php`
  (usa `<x-app-layout :module-id="config('pidgey.portal_app_id')">`) e
  `components/menu-main.blade.php`. Padrão Tailwind/Alpine.
- **Scheduler:** `App\Modules\Pidgey\Console\Commands\EnviarAgendados`
  (`pidgey:enviar-agendados`, `everyMinute` em `routes/console.php`). Envia os
  agendamentos cuja `proxima_execucao` já chegou, reescrevendo no estilo da
  persona quando `interpretar=true` (via `EnvioService`).

## Channels

- **Telegram:** implemented. Uses `Pidgey\Services\TelegramService`, which wraps
  the Telegram Bot API `sendMessage`. The bot token and chat id are read from the
  target `Persona` (Alfred module), which is where all Telegram configs live.
- **WhatsApp / Email:** planned. Add a `Services\*` service per channel and branch
  in `MensagemController`.

## Endpoints

| Método | URI | Descrição |
|--------|-----|-----------|
| GET | `/api/pidgey/health` | Health check (público) |
| POST | `/api/pidgey/mensagens` | Envia mensagem de uma `Persona` via canal |

### POST `/api/pidgey/mensagens`

Body (JSON):
```json
{
  "persona": "luffy",          // slug ou id da Persona (Alfred)
  "mensagem": "Regar as plantas amanhã", // texto da mensagem
  "canal": "telegram",          // opcional; default = canal da persona
  "interpretar": true           // opcional; reescreve a mensagem como a persona
}
```

Quando `interpretar` é `true`, o `InterpretadorPersonaService` usa o Ollama
(`config('services.ollama')`) para reescrever `mensagem` no estilo da persona,
usando o campo `personality` dela como referência. A mensagem final (já
interpretada) é o que vai pro canal. A resposta inclui `mensagem_original` e
`mensagem_enviada` para conferência. Se a IA falhar, envia a mensagem original.

Respostas: `200` ok, `404` persona não encontrada, `422` canal/persona sem
config, `502` falha no envio ao Telegram.

## Interpretação (IA)

- `Services\OllamaService` — wrapper do `OllamaDriver` global apontando para
  `config('services.ollama.base_uri')` (default `http://192.168.15.10:11434`,
  modelo `llama3`).
- `Services\InterpretadorPersonaService` — monta o prompt com o `personality`
  da persona e chama o Ollama. Não depende do provedor de IA global do Admin.

## Cross-Module Dependencies

- `App\Modules\Alfred\Models\Persona` — personas (e seus tokens de Telegram) são
  configuradas no módulo Alfred. Pidgey apenas as consulta.
