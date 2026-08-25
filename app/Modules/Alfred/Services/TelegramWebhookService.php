<?php

namespace App\Modules\Alfred\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookService
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.telegram.timeout', 15);
    }

    /**
     * Endereço padrão do webhook: aponta para o gerenciador de webhook do
     * módulo Pidgey, levando o slug da persona como parâmetro de rota.
     */
    public function endpointPadrao(string $slug): string
    {
        return route('pidgey.api.webhook.telegram', ['persona' => $slug]);
    }

    /**
     * Configura o webhook do bot para a URL informada.
     *
     * @return array{ok:bool,status:?int,body:?string,error:?string}
     */
    public function configurar(string $token, string $url): array
    {
        if (empty($token) || empty($url)) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'token ou url ausente'];
        }

        try {
            $endpoint = 'https://api.telegram.org/bot'.$token.'/setWebhook';

            $req = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'url' => $url,
                ]);

            $status = $req->status();
            $body = $req->body();

            if (! $req->successful()) {
                Log::error('Telegram setWebhook falhou', ['status' => $status, 'body' => $body]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed'];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Telegram setWebhook exceção', ['error' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove o webhook configurado no bot.
     *
     * @return array{ok:bool,status:?int,body:?string,error:?string}
     */
    public function remover(string $token): array
    {
        if (empty($token)) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'token ausente'];
        }

        try {
            $endpoint = 'https://api.telegram.org/bot'.$token.'/deleteWebhook';

            $req = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'drop_pending_updates' => true,
                ]);

            $status = $req->status();
            $body = $req->body();

            if (! $req->successful()) {
                Log::error('Telegram deleteWebhook falhou', ['status' => $status, 'body' => $body]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed'];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Telegram deleteWebhook exceção', ['error' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Retorna as informações atuais do webhook do bot.
     *
     * @return array{ok:bool,info:?array,error:?string}
     */
    public function info(string $token): array
    {
        if (empty($token)) {
            return ['ok' => false, 'info' => null, 'error' => 'token ausente'];
        }

        try {
            $endpoint = 'https://api.telegram.org/bot'.$token.'/getWebhookInfo';

            $req = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->get($endpoint);

            if (! $req->successful()) {
                return ['ok' => false, 'info' => null, 'error' => 'request_failed'];
            }

            $data = $req->json() ?? [];
            $result = $data['result'] ?? null;

            return ['ok' => true, 'info' => is_array($result) ? $result : null, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Telegram getWebhookInfo exceção', ['error' => $e->getMessage()]);

            return ['ok' => false, 'info' => null, 'error' => $e->getMessage()];
        }
    }
}
