<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\DailyNotification;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Services\AI\Drivers\AirForceDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\KdjingpaiDriver;
use App\Services\AI\Drivers\LmStudioDriver;
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
        $service = new AiProviderService;
        $provider = $service->getImageToImageProvider();

        if ($provider && $provider->provedor) {
            $this->driver = $service->getDriverForProvider($provider);
            $this->model = $provider->model;
            $this->apiKey = $service->getApiKeyForProvider($provider);
            $this->baseUrl = $service->getBaseUrlForProvider($provider);
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

        $prompt = $this->buildPrompt($userAttr);
        $imageUrl = $this->generateImage($userAttr, $prompt);

        if (! $imageUrl) {
            Log::error("DailyPhotoService: Falha ao gerar imagem para usuário {$userAttr->user_id}");

            return false;
        }

        $generation = $this->saveGeneration($userAttr, $imageUrl, $prompt);

        return $this->sendNotification($userAttr, $imageUrl, $prompt, $preference, $generation?->id);
    }

    protected function generateImage(UserAttribute $userAttr, string $prompt): ?string
    {
        if (! $this->driver) {
            Log::error('DailyPhotoService: Nenhum provedor de IA ativo configurado');

            return null;
        }

        $options = [];
        if ($userAttr->photo_path && in_array($this->driver, ['pollination', 'airforce'], true)) {
            $options['reference_image_path'] = $userAttr->photo_path;
        }

        $driver = $this->createDriver();

        return $driver->generateImage($prompt, $options);
    }

    protected function buildPrompt(UserAttribute $userAttr): string
    {
        return $userAttr->buildImageGenerationPrompt(
            'Crie a imagem do dia desta pessoa usando o perfil completo para definir visual, energia, estilo, cenarios e preferencias.'
        );
    }

    protected function createDriver()
    {
        return match ($this->driver) {
            'airforce' => new AirForceDriver($this->model, $this->apiKey, $this->baseUrl),
            'kdjingpai' => new KdjingpaiDriver($this->model, $this->apiKey, $this->baseUrl),
            default => new PollinationDriver($this->model, $this->apiKey, $this->baseUrl),
        };
    }

    public function sendNotification(UserAttribute $userAttr, string $imageUrl, string $prompt, string $preference, ?int $generationId = null): bool
    {
        $user = $userAttr->user;

        if (! $user) {
            return false;
        }

        $notification = DailyNotification::create([
            'user_id' => $user->id,
            'generation_id' => $generationId,
            'image_url' => $imageUrl,
            'channel' => $preference,
            'sent' => false,
        ]);

        $result = match ($preference) {
            'email' => $this->sendEmail($user, $imageUrl, $prompt, $notification),
            'telegram' => $this->sendTelegram($user, $imageUrl, $prompt, $notification),
            'whatsapp' => $this->sendWhatsapp($user, $imageUrl, $prompt, $notification),
            default => false,
        };

        return $result;
    }

    protected function saveGeneration(UserAttribute $userAttr, string $imageUrl, string $prompt): ?DailyGeneration
    {
        try {
            return DailyGeneration::create([
                'user_id' => $userAttr->user_id,
                'image_url' => $imageUrl,
                'final_prompt_used' => $prompt,
                'reference_date' => now()->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DailyPhotoService: Erro ao salvar geração: '.$e->getMessage());

            return null;
        }
    }

    protected function sendEmail($user, string $imageUrl, string $prompt, DailyNotification $notification): bool
    {
        try {
            $emailBody = $this->generateEmailBody($user, $imageUrl, $prompt, $notification->generation_id);

            Mail::send([], [], function ($message) use ($user, $emailBody) {
                $message->to($user->email, $user->name)
                    ->subject('🌟 Sua Foto do Dia!')
                    ->html($emailBody);
            });

            Log::info("DailyPhotoService: Email enviado para {$user->email}");

            $notification->markAsSent($emailBody);

            return true;
        } catch (\Exception $e) {
            Log::error("DailyPhotoService: Erro ao enviar email para {$user->email}: ".$e->getMessage());

            $notification->markAsFailed($e->getMessage());

            return false;
        }
    }

    protected function generateEmailBody($user, string $imageUrl, string $prompt, ?int $generationId = null): string
    {
        try {
            $aiProviderService = new AiProviderService;
            $provider = $aiProviderService->getTextToTextProvider();

            if (! $provider) {
                return $this->getDefaultEmailBody($user, $imageUrl, $generationId);
            }

            $driverName = $aiProviderService->getDriverForProvider($provider);
            $apiKey = $aiProviderService->getApiKeyForProvider($provider);
            $baseUrl = $aiProviderService->getBaseUrlForProvider($provider);
            $model = $provider->model;

            $driver = $this->createTextDriver($driverName, $model, $apiKey, $baseUrl);

            if (! $driver) {
                return $this->getDefaultEmailBody($user, $imageUrl, $generationId);
            }

            $messages = $this->buildTextPrompt($prompt, $user->name);

            $response = $driver->generateText($messages, []);

            if ($response) {
                return $this->formatEmailBody($user, $imageUrl, $response, $generationId);
            }

            return $this->getDefaultEmailBody($user, $imageUrl, $generationId);
        } catch (\Exception $e) {
            Log::error('DailyPhotoService: Erro ao gerar corpo do email: '.$e->getMessage());

            return $this->getDefaultEmailBody($user, $imageUrl, $generationId);
        }
    }

    protected function buildTextPrompt(string $imagePrompt, string $userName): array
    {
        return [
            [
                'role' => 'user',
                'content' => "Com base no seguinte prompt de geração de imagem, escreva uma mensagem calorosa e motivadora para o usuário em português brasileiro:\n\n\"{$imagePrompt}\"\n\nEscreva 2-3 parágrafos curtos, friendly e inspirador. Foque na emoção e energia do que foi gerado. Não inclua saudação com nome próprio, apenas a mensagem.",
            ],
        ];
    }

    protected function createTextDriver(string $driverName, ?string $model, ?string $apiKey, ?string $baseUrl)
    {
        return match ($driverName) {
            'airforce' => new AirForceDriver($model, $apiKey, $baseUrl),
            'kdjingpai' => new KdjingpaiDriver($model, $apiKey, $baseUrl),
            'gemini' => new GeminiDriver($apiKey),
            'lm_studio' => new LmStudioDriver($baseUrl),
            default => new PollinationDriver($model, $apiKey, $baseUrl),
        };
    }

    protected function generateRatingHtml(?int $generationId): string
    {
        if (! $generationId) {
            return '';
        }

        $html = "<div style='text-align: center; margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 10px;'>";
        $html .= "<p style='color: #475569; font-weight: bold; margin-bottom: 10px;'>Como você avalia a imagem de hoje?</p>";
        $html .= "<div style='font-size: 32px;'>";

        for ($i = 1; $i <= 5; $i++) {
            $url = \Illuminate\Support\Facades\URL::signedRoute('mundos-de-mim.rate', ['generation' => $generationId, 'rating' => $i]);
            $html .= "<a href='{$url}' style='text-decoration: none; color: #fbbf24; margin: 0 5px;'>★</a>";
        }

        $html .= '</div>';
        $html .= "<p style='color: #64748b; font-size: 12px; margin-top: 10px;'>Ao avaliar, você será automaticamente logado na sua galeria.</p>";
        $html .= '</div>';

        return $html;
    }

    protected function formatEmailBody($user, string $imageUrl, string $aiMessage, ?int $generationId = null): string
    {
        $ratingHtml = $this->generateRatingHtml($generationId);

        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; color: #1e1e24;'>
                <h1 style='color: #6366f1;'>Olá, {$user->name}!</h1>
                <div style='background-color: #ede9fe; border-left: 4px solid #6366f1; padding: 20px; border-radius: 0 10px 10px 0; margin: 20px 0;'>
                    <p style='color: #3730a3; font-size: 18px; margin: 0;'>{$aiMessage}</p>
                </div>
                <p><a href='{$imageUrl}' target='_blank'><img src='{$imageUrl}' style='max-width: 100%; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'/></a></p>
                <p><a href='{$imageUrl}' target='_blank' style='color: #6366f1;'>Clique aqui para ver a imagem em tamanho maior</a></p>
                {$ratingHtml}
                <br>
                <p style='color: #555555; font-size: 14px;'>
                    Com carinho,<br>
                    Equipe Mundos de Mim
                </p>
            </div>
        ";
    }

    protected function getDefaultEmailBody($user, string $imageUrl, ?int $generationId = null): string
    {
        $ratingHtml = $this->generateRatingHtml($generationId);

        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; color: #1e1e24;'>
                <h1 style='color: #6366f1;'>Olá, {$user->name}!</h1>
                <p style='color: #333333;'>Sua foto do dia foi gerada!</p>
                <p><a href='{$imageUrl}' target='_blank'><img src='{$imageUrl}' style='max-width: 100%; border-radius: 10px;'/></a></p>
                <p><a href='{$imageUrl}' target='_blank' style='color: #6366f1;'>Clique aqui para ver a imagem em tamanho maior</a></p>
                <br>
                <p style='color: #555555; font-size: 14px;'>Atenciosamente,<br>Equipe Mundos de Mim</p>
            </div>
        ";
    }

    protected function sendTelegram($user, string $imageUrl, string $prompt, DailyNotification $notification): bool
    {
        $telegramId = $user->telegram_id ?? null;

        if (! $telegramId) {
            Log::warning("DailyPhotoService: Usuário {$user->id} não tem Telegram ID configurado");

            $notification->markAsFailed('Telegram ID não configurado');

            return false;
        }

        try {
            $messageText = "🌟 *Sua Foto do Dia!* \n\n";
            $messageText .= "Olá, {$user->name}! Sua foto foi gerada:\n";
            $messageText .= "[Ver Imagem]({$imageUrl})";

            // Aqui você integraria com a API do Telegram
            // Exemplo: Http::post("https://api.telegram.org/bot{$token}/sendMessage", [...]);

            Log::info("DailyPhotoService: Telegram enviado para user_id {$telegramId}");

            $notification->markAsSent($messageText);

            return true;
        } catch (\Exception $e) {
            Log::error('DailyPhotoService: Erro ao enviar Telegram: '.$e->getMessage());

            $notification->markAsFailed($e->getMessage());

            return false;
        }
    }

    protected function sendWhatsapp($user, string $imageUrl, string $prompt, DailyNotification $notification): bool
    {
        // Implementação futura quando a API do WhatsApp estiver disponível
        Log::info("DailyPhotoService: WhatsApp ainda não implementado para usuário {$user->id}");

        $notification->markAsFailed('WhatsApp não implementado');

        return false;
    }
}
