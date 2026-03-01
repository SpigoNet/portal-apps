<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AirForceDriver implements AiDriverInterface
{
    protected string $baseUrl = 'https://api.airforce';

    protected string $textEndpoint = 'https://api.airforce/v1/chat/completions';

    protected string $imageEndpoint = 'https://api.airforce/v1/images/generations';

    protected ?string $apiKey = null;

    protected string $model = 'glm-4-flash';

    public function __construct(?string $model = null, ?string $apiKey = null, ?string $baseUrl = null)
    {
        if ($model) {
            $this->model = $model;
        }

        $this->apiKey = $apiKey ?: env('AIRFORCE_API_KEY');

        if ($baseUrl) {
            $normalizedBase = rtrim($baseUrl, '/');
            $this->textEndpoint = $normalizedBase.'/v1/chat/completions';
            $this->imageEndpoint = $normalizedBase.'/v1/images/generations';
        }
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => false,
        ], $options);

        unset($payload['jsonMode']);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($this->textEndpoint, $payload);

            Log::debug('AirForce Text Response: '.$response->body());

            if ($response->successful()) {
                $data = $response->json();

                return $data['choices'][0]['message']['content'] ?? $response->body();
            }

            Log::error('AirForce Text Failed: '.$response->status().' - '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('AirForce Text Exception: '.$e->getMessage());

            return null;
        }
    }

    public function generateImage(string $prompt, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => $options['model'] ?? 'nano-banana-pro',
            'prompt' => $prompt,
            'n' => $options['n'] ?? 1,
            'size' => $options['size'] ?? '1024x1024',
            'response_format' => 'url',
            'sse' => false,
            'aspectRatio' => $options['aspect_ratio'] ?? '1:1',
            'resolution' => $options['resolution'] ?? '1k',
        ], $options);

        unset($payload['aspect_ratio'], $payload['seed'], $payload['reference_image_path']);

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if (! empty($this->apiKey)) {
                $headers['Authorization'] = 'Bearer '.$this->apiKey;
            }

            Log::debug('AirForce Image Request', ['payload' => $payload, 'endpoint' => $this->imageEndpoint]);

            $response = Http::withHeaders($headers)
                ->timeout(90)
                ->post($this->imageEndpoint, $payload);

            Log::debug('AirForce Image Response: '.$response->body());

            if ($response->successful()) {
                $data = $response->json();

                // Formato: { "data": [{ "url": "..." }] }
                if (! empty($data['data'][0]['url'])) {
                    return $data['data'][0]['url'];
                }

                // Formato alternativo: { "url": "..." }
                if (! empty($data['url'])) {
                    return $data['url'];
                }

                // Formato: { "data": [{ "b64_json": "..." }] }
                if (! empty($data['data'][0]['b64_json'])) {
                    $imageData = base64_decode($data['data'][0]['b64_json']);
                    if ($imageData) {
                        $filename = 'generations/airforce_'.Str::uuid().'.png';
                        Storage::disk('public')->put($filename, $imageData);

                        return Storage::disk('public')->url($filename);
                    }
                }

                Log::error('AirForce Image: URL not found in response', ['data' => $data]);

                return null;
            }

            Log::error('AirForce Image Failed: '.$response->status().' - '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('AirForce Image Exception: '.$e->getMessage());

            return null;
        }
    }
}
