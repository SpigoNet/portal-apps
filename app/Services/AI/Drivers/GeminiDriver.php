<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiDriver implements AiDriverInterface
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent';
    protected string $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            // Extrai instrução de sistema
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => ['text' => $msg['content']]];
                continue;
            }

            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $parts = [];

            // 1. Texto
            if (!empty($msg['content'])) {
                $parts[] = ['text' => $msg['content']];
            }

            // 2. Imagem (Correção do Storage)
            if (!empty($msg['image'])) {
                try {
                    $imagePath = $msg['image'];
                    $fileContent = null;
                    $mimeType = null;

                    // Tenta primeiro no disco 'public' (onde o upload é feito)
                    if (Storage::disk('public')->exists($imagePath)) {
                        $fileContent = Storage::disk('public')->get($imagePath);
                        $mimeType = Storage::disk('public')->mimeType($imagePath);
                    }
                    // Fallback: Tenta no disco padrão (caso mude a config futura)
                    elseif (Storage::exists($imagePath)) {
                        $fileContent = Storage::get($imagePath);
                        $mimeType = Storage::mimeType($imagePath);
                    }

                    if ($fileContent) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($fileContent)
                            ]
                        ];
                    } else {
                        Log::warning("GeminiDriver: Imagem não encontrada (Public/Default): {$imagePath}");
                    }
                } catch (\Exception $e) {
                    Log::error("GeminiDriver: Erro ao ler imagem: " . $e->getMessage());
                }
            }

            if (!empty($parts)) {
                $contents[] = [
                    'role' => $role,
                    'parts' => $parts
                ];
            }
        }

        // Payload com Safety Settings e Retry (mantido da versão anterior)
        $payload = [
            'contents' => $contents,
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ],
            'generationConfig' => [
                'temperature' => 1.0,
                'maxOutputTokens' => 2000,
                'responseMimeType' => ($options['jsonMode'] ?? false) ? 'application/json' : 'text/plain',
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        return $this->sendRequestWithRetry($payload);
    }

    protected function sendRequestWithRetry(array $payload): ?string
    {
        $url = $this->baseUrl . '?key=' . $this->apiKey;
        $maxRetries = 1;
        $delay = 1000;

        // --- NOVO: Logar o que estamos enviando (CUIDADO COM BASE64 NO LOG) ---
        // Fazemos uma cópia para não lotar o log com o código da imagem
        $debugPayload = $payload;
        if (isset($debugPayload['contents'][0]['parts'][1]['inline_data']['data'])) {
            $debugPayload['contents'][0]['parts'][1]['inline_data']['data'] = '[IMAGEM_BASE64_REMOVIDA_DO_LOG]';
        }
        Log::debug('🤖 [GEMINI REQUEST] Enviando:', $debugPayload);

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $response = Http::withOptions(['connect_timeout' => 15, 'timeout' => 90])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                // --- NOVO: Logar o que voltou ---
                Log::debug('🤖 [GEMINI RESPONSE] Status: ' . $response->status());
                if ($response->failed()) {
                    Log::error('🤖 [GEMINI ERROR] Body: ' . $response->body());
                }
                // --------------------------------

                if ($response->successful()) {
                    Log::debug('🤖 [GEMINI SUCCESS] Body: ' . $response->body());
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }

                if ($response->status() === 429 || $response->status() >= 500) {
                    usleep($delay * 1000);
                    $delay *= 2;
                    continue;
                }

                Log::error("Gemini API Error: " . $response->body());
                return null;

            } catch (\Exception $e) {
                if ($i === $maxRetries - 1) {
                    Log::error("Gemini Connection Error: " . $e->getMessage());
                    return null;
                }
                usleep($delay * 1000);
                $delay *= 2;
            }
        }
        return null;
    }
}
