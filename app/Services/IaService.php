<?php

namespace App\Services;

use App\Services\AI\AiDriverInterface;
use App\Services\AI\Drivers\PollinationDriver;
use App\Services\AI\Drivers\LmStudioDriver;
use App\Services\AI\Drivers\GeminiDriver; // <--- Novo
use App\Modules\ANT\Models\AntConfiguracao;

class IaService
{
    protected AiDriverInterface $driver;

    public function __construct()
    {
        $config = AntConfiguracao::first();

        $driverName = $config->ia_driver ?? 'pollination';
        $url = $config->ia_url ?? 'http://localhost:1234/v1';
        $key = $config->ia_key ?? ''; // <--- Pegamos a chave

        switch ($driverName) {
            case 'gemini':
                // Se não tiver chave configurada, loga erro ou tenta sem (vai falhar)
                $this->driver = new GeminiDriver($key);
                break;

            case 'lm_studio':
                $this->driver = new LmStudioDriver($url);
                break;

            case 'pollination':
            default:
                $this->driver = new PollinationDriver();
                break;
        }
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        return $this->driver->generateText($messages, $options);
    }
}
