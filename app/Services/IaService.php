<?php

namespace App\Services;

use App\Services\AI\AiDriverInterface;
use App\Services\AI\Drivers\PollinationDriver;
use App\Services\AI\Drivers\LmStudioDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\ANT\Models\AntConfiguracao;

class IaService
{
    protected AiDriverInterface $driver;

    public function __construct()
    {
        $aiProviderService = new AiProviderService();
        $config = AntConfiguracao::first();

        // Tenta pegar o driver do sistema centralizado (Admin)
        // Se não houver um usuário logado (ex: cron), pegamos o padrão do sistema.
        $driverName = $aiProviderService->getDriverForUser(null);
        $key = $aiProviderService->getApiKeyForUser(null);
        $url = $aiProviderService->getBaseUrlForUser(null);
        $model = $aiProviderService->getModelForUser(null);

        // Fallback para AntConfiguracao se o Admin não tiver nada (compatibilidade)
        if ($driverName === 'pollination' && empty($key)) {
             if ($config) {
                 $driverName = $config->ia_driver ?? 'pollination';
                 $url = $config->ia_url ?? $url;
                 $key = $config->ia_key ?? $key;
             }
        }

        switch ($driverName) {
            case 'gemini':
                $this->driver = new GeminiDriver($key);
                break;

            case 'lm_studio':
                $this->driver = new LmStudioDriver($url);
                break;

            case 'pollination':
            default:
                $this->driver = new PollinationDriver($model, $key, $url);
                break;
        }
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        return $this->driver->generateText($messages, $options);
    }
}
