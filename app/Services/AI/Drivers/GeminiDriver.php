<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiDriver implements AiDriverInterface
{
    // Usaremos o modelo Flash por ser rápido e barato/gratuito
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    protected string $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        // 1. Tradução do formato OpenAI (messages) para Gemini (contents)
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                // O Gemini 1.5 aceita instrução de sistema separada
                $systemInstruction = [
                    'parts' => ['text' => $msg['content']]
                ];
                continue;
            }

            // Mapeia roles: 'user' -> 'user', 'assistant' -> 'model'
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content']]
                ]
            ];
        }

        // 2. Monta o Payload
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
                // Se o Controller pediu JSON, forçamos o MIME type
                'responseMimeType' => ($options['jsonMode'] ?? false) ? 'application/json' : 'text/plain',
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        try {
            // 3. Envia a Requisição (A chave vai na URL)
            $url = $this->baseUrl . '?key=' . $this->apiKey;

            $response = Http::withOptions(['connect_timeout' => 10, 'timeout' => 60])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                // O texto fica em candidates[0].content.parts[0].text
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error("Gemini API Error: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Gemini Exception: " . $e->getMessage());
            return null;
        }
    }
}
