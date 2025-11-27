<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LmStudioDriver implements AiDriverInterface
{
    protected string $baseUrl;

    public function __construct($url = 'http://localhost:1234/v1')
    {
        $url = rtrim($url, '/');
        if (!str_ends_with($url, '/v1')) {
            $url .= '/v1';
        }
        $this->baseUrl = $url . '/chat/completions';
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => 'local-model',
            'temperature' => 0.7,
            'max_tokens' => -1,
            'stream' => false,
            'messages' => $messages,
            // REMOVIDO: 'response_format' => ...
            // O LM Studio vai tratar como texto padrão, e o nosso Prompt
            // garante que o texto gerado seja um JSON.
        ], $options);

        // Remove chaves que não são da API do LM Studio para evitar lixo no payload
        unset($payload['jsonMode']);

        try {
            $response = Http::withOptions(['connect_timeout' => 5, 'timeout' => 120])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error("LM Studio Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("LM Studio Exception: " . $e->getMessage());
            return null;
        }
    }
}
