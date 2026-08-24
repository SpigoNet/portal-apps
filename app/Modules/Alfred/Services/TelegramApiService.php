<?php

namespace App\Modules\Alfred\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiService
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.telegram.timeout', 15);
    }

    public function sendMessage(string $token, string $chatId, string $message): array
    {
        if (empty($token) || empty($chatId)) {
            $msg = 'Telegram API: token or chatId missing';
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
                Log::error('Telegram API send failed', ['status' => $status, 'body' => $body, 'endpoint' => $endpoint]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed'];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Telegram API exception', ['error' => $e->getMessage(), 'endpoint' => ($endpoint ?? null)]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }
}
