<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramLinkService
{
    private const PREFIXO_CACHE = 'mundos-de-mim:telegram-link:';

    private const VALIDADE_MINUTOS = 15;

    public function botUsername(): string
    {
        return (string) config('services.mundos_de_mim.telegram_bot', 'mundosdemim_bot');
    }

    /**
     * Cria um código de uso único e devolve o link que abre o bot já com o código.
     */
    public function criarLink(User $user): string
    {
        $codigo = Str::random(32);

        Cache::put(self::PREFIXO_CACHE.$codigo, $user->id, now()->addMinutes(self::VALIDADE_MINUTOS));

        return 'https://t.me/'.$this->botUsername().'?start='.$codigo;
    }

    public function desconectar(User $user): void
    {
        $user->forceFill(['telegram_id' => null])->save();
    }

    /**
     * Processa uma atualização recebida do Telegram (webhook).
     */
    public function tratarAtualizacao(array $atualizacao): void
    {
        $mensagem = $atualizacao['message'] ?? null;
        $chatId = $mensagem['chat']['id'] ?? null;

        if (! $mensagem || ! $chatId || ($mensagem['chat']['type'] ?? '') !== 'private') {
            return;
        }

        $texto = trim((string) ($mensagem['text'] ?? ''));

        if (preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{16,64})$/', $texto, $m)) {
            $this->conectar((string) $chatId, $m[1]);

            return;
        }

        if (preg_match('/^\/parar(?:@\w+)?$/', $texto)) {
            $this->tratarParar((string) $chatId);

            return;
        }

        $this->enviarMensagem((string) $chatId, "Oi! Eu entrego o seu postal de cada dia.\n\nPara me conectar à sua conta, abra o Retrato no Mundos de Mim e toque em \"Conectar ao Telegram\".\n\nPara parar de receber, envie /parar.");
    }

    private function conectar(string $chatId, string $codigo): void
    {
        $userId = Cache::pull(self::PREFIXO_CACHE.$codigo);
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            $this->enviarMensagem($chatId, 'Esse link expirou ou já foi usado. Volte ao Retrato no Mundos de Mim e toque em "Conectar ao Telegram" de novo.');

            return;
        }

        $outro = User::where('telegram_id', $chatId)->where('id', '!=', $user->id)->exists();

        if ($outro) {
            $this->enviarMensagem($chatId, 'Este Telegram já está ligado a outra conta do portal. Desligue-o lá primeiro e tente de novo.');

            return;
        }

        $user->forceFill(['telegram_id' => $chatId])->save();

        $primeiroNome = Str::before(trim((string) $user->name), ' ');
        $this->enviarMensagem($chatId, "Conectado, {$primeiroNome}! Seu próximo postal chega por aqui.\n\nPara parar de receber, envie /parar.");
    }

    private function tratarParar(string $chatId): void
    {
        $user = User::where('telegram_id', $chatId)->first();

        if ($user) {
            $this->desconectar($user);
            $this->enviarMensagem($chatId, 'Pronto, não envio mais postais por aqui. Para voltar, conecte de novo pelo Retrato.');

            return;
        }

        $this->enviarMensagem($chatId, 'Este Telegram não está conectado a nenhuma conta.');
    }

    public function enviarMensagem(string $chatId, string $texto): void
    {
        $token = (string) config('services.mundos_de_mim.telegram_token', '');

        if ($token === '') {
            return;
        }

        try {
            Http::timeout((int) config('services.telegram.timeout', 15))
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $texto,
                ]);
        } catch (\Throwable $e) {
            Log::warning('TelegramLinkService: erro ao responder no Telegram: '.$e->getMessage());
        }
    }

    /**
     * Registra no Telegram a URL do webhook, protegida por um segredo.
     */
    public function registrarWebhook(string $url): array
    {
        $token = (string) config('services.mundos_de_mim.telegram_token', '');
        $segredo = (string) config('services.mundos_de_mim.telegram_webhook_secret', '');

        $resposta = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            'secret_token' => $segredo,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);

        return $resposta->json() ?? ['ok' => false];
    }
}
