<?php

namespace App\Modules\Pidgey\Services;

use App\Services\AI\Drivers\OllamaDriver;

class OllamaService
{
    private OllamaDriver $driver;

    public function __construct()
    {
        $baseUri = rtrim(config('services.ollama.base_uri', 'http://192.168.15.10:11434'), '/');
        $model = config('services.ollama.model', 'llama3');

        $this->driver = new OllamaDriver($model, null, $baseUri);
    }

    /**
     * Envia mensagens no formato OpenAI (role/content) para o Ollama.
     */
    public function generateText(array $messages, array $options = []): ?string
    {
        return $this->driver->generateText($messages, $options);
    }
}
