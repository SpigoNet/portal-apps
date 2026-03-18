<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiDriver implements AiDriverInterface
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    protected string $apiKey;

    protected string $model = 'gemini-2.5-flash';

    public function __construct(?string $apiKey, ?string $model = null, ?string $baseUrl = null)
    {
        $this->apiKey = (string) $apiKey;

        if ($model) {
            $this->model = $model;
        }

        if ($baseUrl) {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
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

    public function generateImage(string $prompt, array $options = []): ?string
    {
        $parts = [];

        if ($prompt !== '') {
            $parts[] = ['text' => $prompt];
        }

        if (! empty($options['reference_image_path'])) {
            $imagePart = $this->buildInlineImagePart($options['reference_image_path']);

            if ($imagePart) {
                $parts[] = $imagePart;
            }
        }

        if ($parts === []) {
            return null;
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => $parts,
            ]],
        ];

        if (! empty($options['system_instruction'])) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $options['system_instruction']]],
            ];
        }

        $response = $this->sendPayload($payload, $options['model'] ?? $this->model);

        if (! $response) {
            return null;
        }

        foreach (data_get($response, 'candidates.0.content.parts', []) as $part) {
            $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;

            if (! $inlineData || empty($inlineData['data'])) {
                continue;
            }

            $imageData = base64_decode($inlineData['data'], true);
            if ($imageData === false) {
                continue;
            }

            $mimeType = $inlineData['mimeType'] ?? $inlineData['mime_type'] ?? 'image/png';

            return $this->storeGeneratedImage($imageData, $mimeType);
        }

        Log::warning('GeminiDriver: generateImage returned no inline image part.', [
            'response' => $response,
        ]);

        return null;
    }

    protected function sendRequestWithRetry(array $payload): ?string
    {
        $response = $this->sendPayload($payload, $this->model);

        return data_get($response, 'candidates.0.content.parts.0.text');
    }

    protected function sendPayload(array $payload, string $model): ?array
    {
        $url = $this->buildUrlForModel($model);
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
                    return $response->json();
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

    protected function buildUrlForModel(string $model): string
    {
        return rtrim($this->baseUrl, '/').'/'.$model.':generateContent?key='.$this->apiKey;
    }

    protected function buildInlineImagePart(string $imagePath): ?array
    {
        try {
            [$fileContent, $mimeType] = $this->readImageContents($imagePath);

            return [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => base64_encode($fileContent),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('GeminiDriver: Erro ao preparar imagem para generateImage: '.$e->getMessage());

            return null;
        }
    }

    protected function readImageContents(string $imagePath): array
    {
        if (Storage::disk('public')->exists($imagePath)) {
            return [
                Storage::disk('public')->get($imagePath),
                Storage::disk('public')->mimeType($imagePath) ?: 'image/png',
            ];
        }

        if (Storage::exists($imagePath)) {
            return [
                Storage::get($imagePath),
                Storage::mimeType($imagePath) ?: 'image/png',
            ];
        }

        throw new \RuntimeException("Imagem não encontrada: {$imagePath}");
    }

    protected function storeGeneratedImage(string $imageData, string $mimeType): string
    {
        $extension = Str::of($mimeType)
            ->after('/')
            ->before(';')
            ->lower()
            ->value() ?: 'png';

        $filename = 'generations/gemini_'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($filename, $imageData);

        return Storage::disk('public')->url($filename);
    }
}
