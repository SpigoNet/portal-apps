<?php

namespace App\Modules\MundosDeMim\Console\Commands;

use App\Modules\MundosDeMim\Services\TelegramLinkService;
use Illuminate\Console\Command;

class RegistrarWebhookTelegram extends Command
{
    protected $signature = 'mundos-de-mim:telegram-webhook';

    protected $description = 'Registra no Telegram o webhook do bot do Mundos de Mim';

    public function handle(TelegramLinkService $telegram): int
    {
        if (! config('services.mundos_de_mim.telegram_token') || ! config('services.mundos_de_mim.telegram_webhook_secret')) {
            $this->error('Defina MUNDOS_DE_MIM_TELEGRAM_TOKEN e MUNDOS_DE_MIM_TELEGRAM_WEBHOOK_SECRET no .env.');

            return self::FAILURE;
        }

        $url = route('mundos-de-mim.telegram.webhook');
        $resposta = $telegram->registrarWebhook($url);

        if (($resposta['ok'] ?? false) === true) {
            $this->info("Webhook registrado em {$url}");

            return self::SUCCESS;
        }

        $this->error('O Telegram recusou: '.($resposta['description'] ?? 'resposta desconhecida'));

        return self::FAILURE;
    }
}
