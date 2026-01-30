<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PollinationDriver implements AiDriverInterface
{
    protected string $textEndpoint = 'https://gen.pollinations.ai/v1/chat/completions';
    protected string $imageBaseUrl = 'https://gen.pollinations.ai/image/';

    // Adicionei token no header, então aqui fica só para referência se precisar
    protected string $apiKey = 'sk_0gOTqpBGMd0eLlsk1GGBBvLSGF2tVt7g';

    public function generateText(array $messages, array $options = []): ?string
    {
        // ... (Mantém a implementação de texto do Gemini/OpenAI inalterada)
        $payload = array_merge([
            'model' => 'gemini',
            'temperature' => 1,
            'max_tokens' => 2000,
            'stream' => false,
            'messages' => $messages,
            'jsonMode' => $options['jsonMode'] ?? false,
            'token' => $this->apiKey,
        ], $options);

        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 60])
                ->withHeaders(['Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', // Simula navegador
                    'Referer' => 'https://pollinations.ai/'])
                ->post($this->textEndpoint, $payload);

            Log::debug("Pollination Text Response: " . $response->body());
            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? $response->body();
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gera imagem tratando caracteres especiais no prompt e mascarando URL da foto.
     */
    public function generateImage(string $prompt, array $options = []): ?string
    {
        $queryParams = [
            'model' => 'nanobanana',
            'width' => 1024,
            'height' => 1024,
            'seed' => $options['seed'] ?? random_int(1, 999999),
            'nologo' => 'true',
            'safe' => 'true',
            'enhance' => 'true',
        ];

        // 1. Processamento da Imagem de Referência (com TinyURL)
        if (!empty($options['reference_image_path'])) {
            $path = $options['reference_image_path'];
            $originalUrl = Storage::disk('public')->url($path);

            // Garante HTTPS e limpa barras duplas
            $originalUrl = str_replace('http://', 'https://', $originalUrl);
            $originalUrl = preg_replace('#(?<!:)/+#', '/', $originalUrl);

            // Encurta a URL para evitar bloqueio do Cloudflare
            if (!Str::contains($originalUrl, ['localhost', '127.0.0.1'])) {
                $queryParams['image'] = $originalUrl;
            }
        }

        // 2. LIMPEZA E CODIFICAÇÃO DO PROMPT (A Solução do Problema)
        // Remove quebras de linha e barras que quebram a URL, mas mantém pontuação e acentos.
        $cleanPrompt = $this->cleanPrompt($prompt);

        // Codifica para URL (espaço vira %20, ç vira %C3%A7, etc)
        $encodedPrompt = rawurlencode($cleanPrompt);

        // Monta a URL final
        $queryString = http_build_query($queryParams);
        $requestUrl = "{$this->imageBaseUrl}{$encodedPrompt}?{$queryString}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', // Simula navegador
                'Referer' => 'https://pollinations.ai/'
            ])->timeout(90)->get($requestUrl);

            if ($response->successful()) {
                $imageContent = $response->body();

                // Validação de conteúdo
                if (strlen($imageContent) < 1000 || str_starts_with($imageContent, '<')) {
                    Log::error("Pollination: Retorno inválido (HTML).");
                    return null;
                }

                $filename = 'generations/pollinations_' . Str::uuid() . '.jpg';
                Storage::disk('public')->put($filename, $imageContent);

                return Storage::disk('public')->url($filename);
            }

            Log::error("Pollination Failed: " . $response->status() . " URL: " . $requestUrl);
            return null;

        } catch (\Exception $e) {
            Log::error("Pollination Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpa o prompt para evitar caracteres que quebram a URL Path.
     */
    protected function cleanPrompt(string $prompt): string
    {
        // 1. Substitui quebras de linha por espaço
        $prompt = str_replace(["\r\n", "\r", "\n"], ' ', $prompt);

        // 2. Substitui barras (/) e contra-barras (\) por espaço
        // Barras no path da URL confundem o servidor
        $prompt = str_replace(['/', '\\'], ' ', $prompt);

        // 3. Remove caracteres de controle invisíveis
        $prompt = preg_replace('/[\x00-\x1F\x7F]/', '', $prompt);

        // 4. Limita tamanho (opcional, mas seguro para GET request)
        return Str::limit($prompt, 1500, '');
    }
}
