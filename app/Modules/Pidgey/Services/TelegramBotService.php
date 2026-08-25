<?php

namespace App\Modules\Pidgey\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.telegram.timeout', 15);
    }

    /**
     * Retorna a URL da foto de perfil (grande) do bot, ou null em caso de falha.
     * O resultado é cacheado por token por 24h.
     */
    public function fotoUrl(string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $cacheKey = 'telegram_bot_foto_'.md5($token);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($token) {
            $me = $this->getMe($token);

            if ($me === null) {
                return null;
            }

            $fileId = $this->fotoFileId($token, $me['id']);

            if ($fileId === null) {
                return null;
            }

            $filePath = $this->filePath($token, $fileId);

            if ($filePath === null) {
                return null;
            }

            return 'https://api.telegram.org/file/bot'.$token.'/'.$filePath;
        });
    }

    /**
     * Retorna o nome de exibição do bot (first_name ou @username).
     */
    public function nome(string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $cacheKey = 'telegram_bot_nome_'.md5($token);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($token) {
            $me = $this->getMe($token);

            if ($me === null) {
                return null;
            }

            $username = $me['username'] ?? null;

            return $me['first_name'].($username ? ' (@'.$username.')' : '');
        });
    }

    private function getMe(string $token): ?array
    {
        try {
            $req = Http::timeout($this->timeout)
                ->get('https://api.telegram.org/bot'.$token.'/getMe');

            if (! $req->successful()) {
                return null;
            }

            $result = $req->json('result');

            return is_array($result) ? $result : null;
        } catch (\Throwable $e) {
            Log::warning('Telegram getMe falhou', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function fotoFileId(string $token, int $userId): ?string
    {
        try {
            $req = Http::timeout($this->timeout)
                ->get('https://api.telegram.org/bot'.$token.'/getUserProfilePhotos', [
                    'user_id' => $userId,
                    'limit' => 1,
                ]);

            if (! $req->successful()) {
                return null;
            }

            $photos = $req->json('result.photos');

            if (! is_array($photos) || count($photos) === 0) {
                return null;
            }

            $sizes = $photos[0];

            if (! is_array($sizes) || count($sizes) === 0) {
                return null;
            }

            // A última entrada é o maior tamanho disponível.
            $maior = end($sizes);

            return $maior['file_id'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Telegram getUserProfilePhotos falhou', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function filePath(string $token, string $fileId): ?string
    {
        try {
            $req = Http::timeout($this->timeout)
                ->get('https://api.telegram.org/bot'.$token.'/getFile', [
                    'file_id' => $fileId,
                ]);

            if (! $req->successful()) {
                return null;
            }

            return $req->json('result.file_path');
        } catch (\Throwable $e) {
            Log::warning('Telegram getFile falhou', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
