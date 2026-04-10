<?php

namespace App\Services;

use App\Services\AI\AiDriverInterface;
use App\Services\AI\Drivers\PollinationDriver;
use App\Services\AI\Drivers\LmStudioDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\ANT\Models\AntConfiguracao;
use Illuminate\Support\Facades\Schema;

class IaService
{
    protected AiDriverInterface $driver;

    public function __construct()
    {
        $driverName = 'pollination';
        $key = null;
        $url = null;
        $model = null;

        // Tenta pegar o driver do sistema centralizado (Admin)
        // Se não houver um usuário logado (ex: cron), pegamos o padrão do sistema.
        try {
            $aiProviderService = new AiProviderService();
            $driverName = $aiProviderService->getDriverForUser(null);
            $key = $aiProviderService->getApiKeyForUser(null);
            $url = $aiProviderService->getBaseUrlForUser(null);
            $model = $aiProviderService->getModelForUser(null);
        } catch (\Throwable $exception) {
            // Mantém fallback padrão quando as tabelas/configurações de IA não existem.
        }

        $config = null;
        if (Schema::hasTable('ant_configuracoes')) {
            $config = AntConfiguracao::first();
        }

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
