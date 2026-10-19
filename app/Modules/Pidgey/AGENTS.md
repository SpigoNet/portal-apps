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

## Bots por módulo (Telegram)

Cada módulo tem **o seu bot e a sua persona** (`Alfred\Persona`: token, slug, personalidade; ex.: Cardápio = `sanji`).
O Pidgey cuida do canal; o módulo implementa `Contracts\ManipuladorTelegram` (persona, ajuda, mensagem, botão) e se
registra em `Services\TelegramRoteador` (singleton) no seu ServiceProvider.

- **Webhook:** `POST /api/pidgey/webhook/bot/{slug}`, protegido por segredo derivado do slug (`TelegramRoteador::segredo`).
  Registrar no Telegram: `php artisan pidgey:telegram-webhook sanji`.
- **Vínculo:** tela `/pidgey/telegram` gera um código de uso único (10 min); o usuário envia `/start CÓDIGO` a qualquer bot.
  Tabela `pidgey_telegram_vinculos` (um chat por usuário). Chat não vinculado não acessa nada; grupos são ignorados.
- **Dados nunca passam pela IA**; a persona só dá voz a frases curtas (ver `Pidgey\Services\VozPersona` e `Cardapio\Services\Telegram\VozPersona`).

## Conteúdo dinâmico de módulos

Um módulo pode fornecer a mensagem inteira de um agendamento: implementa `Contracts\ProvedorConteudo` (`tipo()` +
`mensagem()`) e se registra em `Services\ProvedoresConteudo` (singleton). O `EnvioService` envia o texto pronto (sem IA)
ao chat vinculado do dono do agendamento; `null` = nada a dizer hoje (não é erro). Ao vincular um chat, o evento
`Events\TelegramVinculado` é disparado para os módulos criarem seus agendamentos (ex.: Cardápio → `resumo_cardapio`).
Sem vínculo de Telegram, o texto vai para o `telegram_chat_id` da própria persona.

Provedores existentes (cada um registrado no `ServiceProvider` do seu módulo):

| Tipo | Persona | Módulo / classe |
|---|---|---|
| `resumo_cardapio` | Sanji | `Cardapio\Services\Telegram\ResumoDiarioProvedor` |
| `relatorio_financeiro` | Nami | `Mithril\Services\Pidgey\RelatorioFinanceiroProvedor` (resumo do relatório + contas a pagar) |
| `contas_a_pagar` | Nami | `Mithril\Services\Pidgey\ContasAPagarProvedor` (atrasadas, de hoje e dos próximos 3 dias; `ContasPendentesService`) |
| `resumo_tarefas` / `fechamento_tarefas` | Zoro | `TreeTask\Services\Pidgey\ResumoTarefasProvedor` (manhã / fim do dia) |
| `contexto_dia` | Luffy | `Alfred\Services\Pidgey\ContextoDiaProvedor` (tarefas, energia, dia ruim, água, rotinas) |

A abertura da persona vem de `Services\VozPersona::abrir($persona, $fatos, $instrucao)` (→ `InterpretadorPersonaService::narrar`,
cuja `$instrucao` troca as regras de cardápio pelas do assunto). Só a abertura passa pela IA; listas, valores e datas são
determinísticos. A migration `2026_10_17_110000_*` cria a persona `zoro` (falta o `telegram_token`), os conteúdos e os
agendamentos padrão (08:30 e 18:00, dias úteis) e troca o texto fixo do Luffy por `contexto_dia`.

## Imagem do personagem (opcional por agendamento)

- `pidgey_agendamentos.enviar_imagem` (checkbox no formulário; ícone de imagem na lista). Na API: `"imagem": true` em `POST /api/pidgey/mensagens`.
- A aparência é fixa e fica no perfil da persona (Alfred): `alfred_personas.aparencia` (texto detalhado) e
  `imagem_referencia` opcional (disco `public`, usada como referência quando o modelo é image→image).
- `Services\ImagemPersonagemService`: a IA de texto (`InterpretadorPersonaService::descreverExpressao`) descreve
  expressão/sentimento/pose da mensagem **final**; o prompt junta aparência + expressão e tenta, em ordem, o padrão
  texto→imagem e o padrão imagem→imagem do portal (com imagem de referência, este vem primeiro), via
  `AiProviderService::createImageDriver()`. O primeiro que devolver imagem vence.
- Parâmetros de geração por provedor (qualidade, tamanho, formato, fidelidade…): `Models\ParametroImagem`
  (`pidgey_parametros_imagem`, independente do MundosDeMim), editados em `/pidgey/parametros-imagem`
  (só admin do app, gate `admin-pidgey`; link "Imagem" no menu). Validação compartilhada em
  `App\Services\AI\ParametrosImagem::sanitizar()`; `ParametroImagem::para($modelo)` vira o `$options` do driver.
- "Enviar agora" com imagem eleva o `set_time_limit` (gerar pode passar dos 30 s padrão do PHP web).
- `EnvioService::entregar()` manda `sendPhoto` com a mensagem como legenda (acima de 1024 caracteres, a legenda vai numa
  mensagem logo depois). Sem aparência, sem modelo de imagem ou com falha → envia só o texto (não é erro).
- O scheduler roda com `withoutOverlapping()`, pois gerar imagem pode passar de 1 minuto.

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
  "interpretar": true,          // opcional; reescreve a mensagem como a persona
  "imagem": true                // opcional; envia junto a imagem do personagem
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
