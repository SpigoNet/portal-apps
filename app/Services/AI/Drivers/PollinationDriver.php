<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollinationDriver implements AiDriverInterface
{
    protected string $endpoint = 'https://text.pollinations.ai/openai';

    public function generateText(array $messages, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => 'openai',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'stream' => false,
            'messages' => $messages,
            'jsonMode' => $options['jsonMode'] ?? false,
        ], $options);

        try {
            $response = Http::withOptions(['verify' => false, 'connect_timeout' => 5, 'timeout' => 60])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                // Tenta padrão OpenAI ou retorna corpo direto (fallback)
                return $data['choices'][0]['message']['content'] ?? $response->body();
            }

            Log::error("Pollination Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Pollination Exception: " . $e->getMessage());
            return null;
        }
    }
}
