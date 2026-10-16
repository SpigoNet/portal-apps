<?php

namespace App\Modules\Pidgey\Console\Commands;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Services\TelegramRoteador;
use App\Modules\Pidgey\Services\TelegramService;
use Illuminate\Console\Command;

class RegistrarWebhookTelegram extends Command
{
    protected $signature = 'pidgey:telegram-webhook {slug : Slug da persona (ex.: sanji)} {--url= : Base pública do portal (padrão: APP_URL)}';

    protected $description = 'Registra no Telegram o webhook do bot de uma persona';

    public function handle(TelegramService $telegram, TelegramRoteador $roteador): int
    {
        $slug = (string) $this->argument('slug');
        $persona = Persona::where('slug', $slug)->first();

        if ($persona === null || empty($persona->telegram_token)) {
            $this->error("Persona '{$slug}' não existe ou está sem telegram_token.");

            return self::FAILURE;
        }

        if ($roteador->manipulador($slug) === null) {
            $this->error("Nenhum módulo atende o bot '{$slug}'.");

            return self::FAILURE;
        }

        $base = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        $url = $base.'/api/pidgey/webhook/bot/'.$slug;

        $r = $telegram->setWebhook((string) $persona->telegram_token, $url, TelegramRoteador::segredo($slug));

        if (! $r['ok']) {
            $this->error('Telegram recusou: '.($r['descricao'] ?? 'erro desconhecido'));

            return self::FAILURE;
        }

        $this->info("Webhook registrado: {$url}");

        return self::SUCCESS;
    }
}
