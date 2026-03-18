<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PollinationImageEditDriver implements AiDriverInterface
{
    protected string $editEndpoint = 'https://gen.pollinations.ai/v1/images/edits';

    protected ?string $apiKey = null;

    protected string $model = 'nanobanana';

    protected ?string $baseUrl = null;

    public function __construct(?string $model = null, ?string $apiKey = null, ?string $baseUrl = null)
    {
        if ($model) {
            $this->model = $model;
        }

        $this->apiKey = $apiKey ?: env('POLLINATIONS_API_KEY');
        $this->baseUrl = $baseUrl ? rtrim($baseUrl, '/') : null;

        if ($this->baseUrl) {
            $this->editEndpoint = $this->baseUrl.'/v1/images/edits';
        }
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        return (new PollinationDriver($options['model'] ?? $this->model, $this->apiKey, $this->baseUrl))
            ->generateText($messages, $options);
    }

    public function generateImage(string $prompt, array $options = []): ?string
    {
        $referenceImagePath = $options['reference_image_path'] ?? null;

        if (! $referenceImagePath) {
            Log::warning('Pollination image edit requires reference_image_path.');

            return null;
        }

        try {
            $request = Http::timeout(90)
                ->acceptJson()
                ->withHeaders($this->buildHeaders());

            $request = $this->attachImage($request, 'image', $referenceImagePath);

            if (! empty($options['mask_image_path'])) {
                $request = $this->attachImage($request, 'mask', $options['mask_image_path']);
            }

            $payload = [
                'model' => $options['model'] ?? $this->model,
                'prompt' => $prompt,
                'n' => $options['n'] ?? 1,
                'response_format' => $options['response_format'] ?? 'b64_json',
            ];

            if (! empty($options['size'])) {
                $payload['size'] = $options['size'];
            } elseif (! empty($options['width']) && ! empty($options['height'])) {
                $payload['size'] = $options['width'].'x'.$options['height'];
            }

            $response = $request->post($this->editEndpoint, $payload);

            if ($response->successful()) {
                return $this->extractImageUrl($response);
            }

            Log::error('Pollination image edit failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Pollination image edit exception: '.$e->getMessage());

            return null;
        }
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Referer' => 'https://pollinations.ai/',
        ];

        if (! empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        return $headers;
    }

    private function attachImage(PendingRequest $request, string $field, string $pathOrUrl): PendingRequest
    {
        [$filename, $contents, $mimeType] = $this->resolveImagePayload($pathOrUrl);

        return $request->attach($field, $contents, $filename, ['Content-Type' => $mimeType]);
    }

    private function resolveImagePayload(string $pathOrUrl): array
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            $response = Http::timeout(30)->get($pathOrUrl);

            if (! $response->successful() || $response->body() === '') {
                throw new \RuntimeException('Unable to download reference image for image edit.');
            }

            $path = parse_url($pathOrUrl, PHP_URL_PATH) ?: '';
            $filename = basename($path) ?: 'reference-image';
            $mimeType = $response->header('Content-Type') ?: 'image/png';

            return [$filename, $response->body(), $mimeType];
        }

        if (! Storage::disk('public')->exists($pathOrUrl)) {
            throw new \RuntimeException("Reference image not found: {$pathOrUrl}");
        }

        $filename = basename($pathOrUrl) ?: 'reference-image';
        $mimeType = Storage::disk('public')->mimeType($pathOrUrl) ?: 'image/png';

        return [$filename, Storage::disk('public')->get($pathOrUrl), $mimeType];
    }

    private function extractImageUrl($response): ?string
    {
        $data = $response->json();

        if (! empty($data['data'][0]['b64_json'])) {
            $imageData = base64_decode($data['data'][0]['b64_json'], true);

            if ($imageData === false) {
                return null;
            }

            return $this->storeImage($imageData, 'png');
        }

        if (! empty($data['data'][0]['url'])) {
            return $data['data'][0]['url'];
        }

        if (! empty($data['url'])) {
            return $data['url'];
        }

        $contentType = $response->header('Content-Type') ?: '';
        if (str_starts_with($contentType, 'image/')) {
            $extension = Str::after($contentType, '/');

            return $this->storeImage($response->body(), $extension ?: 'png');
        }

        Log::error('Pollination image edit returned an unsupported payload.', [
            'body' => $response->body(),
        ]);

        return null;
    }

    private function storeImage(string $contents, string $extension): string
    {
        $filename = 'generations/pollinations-edit_'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($filename, $contents);

        return Storage::disk('public')->url($filename);
    }
}
