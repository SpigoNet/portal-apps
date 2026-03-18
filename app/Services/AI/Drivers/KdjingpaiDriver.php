<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KdjingpaiDriver implements AiDriverInterface
{
    protected string $baseUrl = 'https://img.kdjingpai.com/';

    public function __construct(?string $model = null, ?string $apiKey = null, ?string $baseUrl = null)
    {
        if ($baseUrl) {
            $this->baseUrl = rtrim($baseUrl, '/') . '/';
        }
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        return null; // Não suporta geração de texto
    }

    public function generateImage(string $prompt, array $options = []): ?string
    {
        $cleanPrompt = $this->cleanPrompt($prompt);

        $queryParams = [
            'prompt' => $cleanPrompt,
            'size' => $options['size'] ?? '512x512',
            'optimization' => $options['optimization'] ?? ($options['optimize'] ?? 0),
        ];

        $requestUrl = $this->baseUrl . '?' . http_build_query($queryParams);

        try {
            $response = Http::timeout(90)->get($requestUrl);

            if ($response->successful()) {
                $imageContent = $response->body();

                // Validação mínima de conteúdo (deve ser uma imagem, não HTML/Erro)
                if (strlen($imageContent) < 1000 || str_starts_with($imageContent, '<')) {
                    Log::error('Kdjingpai: Retorno inválido (não parece uma imagem). Body: ' . substr($imageContent, 0, 100));
                    return null;
                }

                $extension = 'jpg';
                $contentType = $response->header('Content-Type');
                if (Str::contains($contentType, 'image/png')) {
                    $extension = 'png';
                } elseif (Str::contains($contentType, 'image/webp')) {
                    $extension = 'webp';
                }

                $filename = 'generations/kdjingpai_' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->put($filename, $imageContent);

                return Storage::disk('public')->url($filename);
            }

            Log::error('Kdjingpai Failed: ' . $response->status() . ' URL: ' . $requestUrl);
            return null;

        } catch (\Exception $e) {
            Log::error('Kdjingpai Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function cleanPrompt(string $prompt): string
    {
        $prompt = preg_replace('/[^\p{L}\p{N}\s,.-]/u', '', $prompt);
        return trim($prompt);
    }
}
