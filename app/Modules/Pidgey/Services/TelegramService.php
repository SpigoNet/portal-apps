<?php

namespace App\Modules\Pidgey\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     * Envia uma foto com legenda. A imagem é enviada como arquivo (multipart), porque a URL do
     * portal nem sempre é acessível pelo Telegram. Legendas acima de 1024 caracteres (limite do
     * Telegram) vão numa mensagem de texto logo depois da foto.
     *
     * @param  string  $imagem  URL (do disco `public` ou externa) ou caminho no disco `public`
     */
    public function sendPhoto(string $token, string $chatId, string $imagem, string $legenda = ''): array
    {
        if (empty($token) || empty($chatId)) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'Telegram: token ou chat_id ausente'];
        }

        $arquivo = $this->lerImagem($imagem);

        if ($arquivo === null) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'Imagem indisponível para envio'];
        }

        $legendaCabe = mb_strlen($legenda) <= 1024;

        try {
            $req = Http::timeout(max($this->timeout, 60))
                ->attach('photo', $arquivo, 'personagem.'.$this->extensao($imagem))
                ->post('https://api.telegram.org/bot'.$token.'/sendPhoto', array_filter([
                    'chat_id' => $chatId,
                    'caption' => $legendaCabe && $legenda !== '' ? $legenda : null,
                    'parse_mode' => $legendaCabe && $legenda !== '' ? 'HTML' : null,
                ], fn ($v) => $v !== null));

            if (! $req->successful()) {
                Log::error('Telegram sendPhoto falhou', ['status' => $req->status(), 'body' => $req->body()]);

                return ['ok' => false, 'status' => $req->status(), 'body' => $req->body(), 'error' => 'request_failed'];
            }
        } catch (\Exception $e) {
            Log::error('Telegram sendPhoto exceção', ['error' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }

        if (! $legendaCabe) {
            return $this->sendMessage($token, $chatId, $legenda);
        }

        return ['ok' => true, 'status' => $req->status(), 'body' => $req->body(), 'error' => null];
    }

    private function lerImagem(string $imagem): ?string
    {
        $disco = Storage::disk('public');
        $prefixo = rtrim($disco->url(''), '/').'/';
        $caminho = str_starts_with($imagem, $prefixo) ? substr($imagem, strlen($prefixo)) : $imagem;

        if (! str_starts_with($caminho, 'http') && $disco->exists($caminho)) {
            return $disco->get($caminho);
        }

        if (! str_starts_with($imagem, 'http')) {
            return null;
        }

        try {
            $resposta = Http::timeout(max($this->timeout, 60))->get($imagem);

            return $resposta->successful() && $resposta->body() !== '' ? $resposta->body() : null;
        } catch (\Exception $e) {
            Log::warning('Telegram: não foi possível baixar a imagem', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function extensao(string $imagem): string
    {
        $ext = strtolower(pathinfo((string) parse_url($imagem, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'png';
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
