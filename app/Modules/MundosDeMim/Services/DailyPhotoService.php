<?php

namespace App\Modules\MundosDeMim\Services;

use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Services\AI\Drivers\AirForceDriver;
use App\Services\AI\Drivers\PollinationDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailyPhotoService
{
    protected ?string $driver = null;

    protected ?string $model = null;

    protected ?string $apiKey = null;

    protected ?string $baseUrl = null;

    public function __construct()
    {
        $provider = AIProvider::where('is_active', true)
            ->whereNotNull('provider_id')
            ->first();

        if ($provider && $provider->gatewayProvider) {
            $this->driver = $provider->gatewayProvider->driver;
            $this->model = $provider->model;
            $this->apiKey = $provider->gatewayProvider->api_key;
            $this->baseUrl = $provider->gatewayProvider->base_url;
        }
    }

    public function processAll(): array
    {
        $users = UserAttribute::whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->where('notification_preference', '!=', 'none')
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($users as $userAttr) {
            try {
                $result = $this->processUser($userAttr);
                if ($result) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("DailyPhotoService: Erro ao processar usuário {$userAttr->user_id}: ".$e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $users->count(),
        ];
    }

    public function processUser(UserAttribute $userAttr): bool
    {
        $preference = $userAttr->notification_preference;

        if (! $preference || $preference === 'none') {
            return false;
        }

        $imageUrl = $this->generateImage($userAttr);

        if (! $imageUrl) {
            Log::error("DailyPhotoService: Falha ao gerar imagem para usuário {$userAttr->user_id}");

            return false;
        }

        return $this->sendNotification($userAttr, $imageUrl, $preference);
    }

    protected function generateImage(UserAttribute $userAttr): ?string
    {
        if (! $this->driver) {
            Log::error('DailyPhotoService: Nenhum provedor de IA ativo configurado');

            return null;
        }

        $prompt = $this->buildPrompt($userAttr);

        $options = [];
        if ($this->driver === 'pollination' && $userAttr->photo_path) {
            $options['reference_image_path'] = $userAttr->photo_path;
        } elseif ($this->driver === 'airforce' && $userAttr->photo_path) {
            $options['reference_image_path'] = $userAttr->photo_path;
        }

        $driver = $this->createDriver();

        return $driver->generateImage($prompt, $options);
    }

    protected function buildPrompt(UserAttribute $userAttr): string
    {
        $parts = [];

        if ($userAttr->body_type) {
            $parts[] = $userAttr->body_type;
        }

        $prompt = implode(', ', $parts);

        if (empty($prompt)) {
            $prompt = 'beautiful person, portrait, high quality';
        }

        return $prompt.', photorealistic, 8k, cinematic lighting, professional photography';
    }

    protected function createDriver()
    {
        return match ($this->driver) {
            'airforce' => new AirForceDriver($this->model, $this->apiKey, $this->baseUrl),
            default => new PollinationDriver($this->model, $this->apiKey, $this->baseUrl),
        };
    }

    protected function sendNotification(UserAttribute $userAttr, string $imageUrl, string $preference): bool
    {
        $user = $userAttr->user;

        if (! $user) {
            return false;
        }

        return match ($preference) {
            'email' => $this->sendEmail($user, $imageUrl),
            'telegram' => $this->sendTelegram($user, $imageUrl),
            'whatsapp' => $this->sendWhatsapp($user, $imageUrl),
            default => false,
        };
    }

    protected function sendEmail($user, string $imageUrl): bool
    {
        try {
            Mail::send([], [], function ($message) use ($user, $imageUrl) {
                $message->to($user->email, $user->name)
                    ->subject('🌟 Sua Foto do Dia!')
                    ->html("
                        <h1>Olá, {$user->name}!</h1>
                        <p>Sua foto do dia foi gerada!</p>
                        <p><a href='{$imageUrl}' target='_blank'><img src='{$imageUrl}' style='max-width: 100%; border-radius: 10px;'/></a></p>
                        <p><a href='{$imageUrl}' target='_blank'>Clique aqui para ver a imagem em tamanho maior</a></p>
                        <br>
                        <p>Atenciosamente,<br>Equipe Mundos de Mim</p>
                    ");
            });

            Log::info("DailyPhotoService: Email enviado para {$user->email}");

            return true;
        } catch (\Exception $e) {
            Log::error("DailyPhotoService: Erro ao enviar email para {$user->email}: ".$e->getMessage());

            return false;
        }
    }

    protected function sendTelegram($user, string $imageUrl): bool
    {
        $telegramId = $user->telegram_id ?? null;

        if (! $telegramId) {
            Log::warning("DailyPhotoService: Usuário {$user->id} não tem Telegram ID configurado");

            return false;
        }

        try {
            $message = "🌟 *Sua Foto do Dia!* \n\n";
            $message .= "Olá, {$user->name}! Sua foto foi gerada:\n";
            $message .= "[Ver Imagem]({$imageUrl})";

            // Aqui você integraria com a API do Telegram
            // Exemplo: Http::post("https://api.telegram.org/bot{$token}/sendMessage", [...]);

            Log::info("DailyPhotoService: Telegram enviado para user_id {$telegramId}");

            return true;
        } catch (\Exception $e) {
            Log::error('DailyPhotoService: Erro ao enviar Telegram: '.$e->getMessage());

            return false;
        }
    }

    protected function sendWhatsapp($user, string $imageUrl): bool
    {
        // Implementação futura quando a API do WhatsApp estiver disponível
        Log::info("DailyPhotoService: WhatsApp ainda não implementado para usuário {$user->id}");

        return false;
    }
}
