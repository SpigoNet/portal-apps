<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaDriver implements AiDriverInterface
{
    protected string $baseUrl;

    protected string $model;

    public function __construct(?string $model = null, ?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: 'http://localhost:11434', '/');
        $this->model = $model ?: 'llama3';
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => $options['model'] ?? $this->model,
            'temperature' => 0.7,
            'stream' => false,
            'messages' => $messages,
        ], $options);

        unset($payload['jsonMode']);

        try {
            $response = Http::withOptions(['connect_timeout' => 5, 'timeout' => 120])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl.'/v1/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Ollama Error: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Ollama Exception: '.$e->getMessage());

            return null;
        }
    }

    public function generateImage(string $prompt, array $options = []): ?string
    {
        Log::warning('OllamaDriver: generateImage não é suportado pelo Ollama.');

        return null;
    }
}
