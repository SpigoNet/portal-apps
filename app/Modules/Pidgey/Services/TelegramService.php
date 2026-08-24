<?php

namespace App\Modules\Pidgey\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.telegram.timeout', 15);
    }

    public function sendMessage(string $token, string $chatId, string $message): array
    {
        if (empty($token) || empty($chatId)) {
            $msg = 'Telegram: token ou chat_id ausente';
            Log::warning($msg, ['chat_id' => $chatId]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $msg];
        }

        try {
            $endpoint = 'https://api.telegram.org/bot'.$token.'/sendMessage';

            $req = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);

            $status = $req->status();
            $body = $req->body();

            if (! $req->successful()) {
                Log::error('Telegram envio falhou', ['status' => $status, 'body' => $body]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed'];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Telegram exceção', ['error' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }
}
