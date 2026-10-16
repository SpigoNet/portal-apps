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

    /**
     * @param  array<string, mixed>|null  $replyMarkup  ex.: teclado inline do Telegram
     */
    public function sendMessage(string $token, string $chatId, string $message, ?array $replyMarkup = null): array
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
                ->post($endpoint, array_filter([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $replyMarkup,
                ], fn ($v) => $v !== null));

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

    /**
     * Confirma o toque num botão inline (tira o "carregando" do botão).
     */
    public function answerCallbackQuery(string $token, string $callbackId): void
    {
        if ($token === '' || $callbackId === '') {
            return;
        }

        try {
            Http::timeout($this->timeout)->post('https://api.telegram.org/bot'.$token.'/answerCallbackQuery', ['callback_query_id' => $callbackId]);
        } catch (\Exception $e) {
            Log::warning('Telegram answerCallbackQuery falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Registra o webhook do bot, com o segredo que o Telegram devolve no cabeçalho de cada chamada.
     *
     * @return array{ok: bool, descricao: ?string}
     */
    public function setWebhook(string $token, string $url, string $segredo): array
    {
        $req = Http::timeout($this->timeout)->post('https://api.telegram.org/bot'.$token.'/setWebhook', [
            'url' => $url,
            'secret_token' => $segredo,
            'allowed_updates' => ['message', 'edited_message', 'callback_query'],
        ]);

        return ['ok' => (bool) $req->json('ok'), 'descricao' => $req->json('description')];
    }
}
