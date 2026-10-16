<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\AiDriverInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Driver para qualquer servidor compatível com a API da OpenAI
 * (ex.: o container spigo-codex-gateway, que usa o CLI do Codex).
 *
 * Texto: /v1/chat/completions. Imagem: /v1/images/generations, ou
 * /v1/images/edits quando há imagem de referência.
 */
class OpenAiCompatibleDriver implements AiDriverInterface
{
    protected string $baseUrl = 'http://spigo-codex-gateway:8000';

    protected ?string $apiKey = null;

    protected string $model = 'gpt-image-1';

    public function __construct(?string $model = null, ?string $apiKey = null, ?string $baseUrl = null)
    {
        if ($model) {
            $this->model = $model;
        }

        $this->apiKey = $apiKey ?: env('CODEX_GATEWAY_TOKEN');

        $baseUrl = $baseUrl ?: env('CODEX_GATEWAY_URL');
        if ($baseUrl) {
            $this->baseUrl = preg_replace('#/v1/?$#', '', rtrim($baseUrl, '/'));
        }
    }

    public function generateText(array $messages, array $options = []): ?string
    {
        if (! empty($options['jsonMode'])) {
            array_unshift($messages, ['role' => 'system', 'content' => 'Responda somente com JSON válido, sem texto fora do JSON.']);
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
        ];

        if (! empty($options['reasoning_effort'])) {
            $payload['reasoning_effort'] = $options['reasoning_effort'];
        }

        try {
            $response = $this->http(300)->post($this->baseUrl.'/v1/chat/completions', $payload);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error('OpenAI-compatible text failed: '.$response->status().' - '.$response->body());

            return null;
        } catch (\Throwable $e) {
            Log::error('OpenAI-compatible text exception: '.$e->getMessage());

            return null;
        }
    }

    public function generateImage(string $prompt, array $options = []): ?string
    {
        $size = $options['size'] ?? (! empty($options['width']) && ! empty($options['height'])
            ? $options['width'].'x'.$options['height']
            : null);

        try {
            if (! empty($options['reference_image_path'])) {
                $request = $this->http(600);
                $request = $this->attachImage($request, 'image', $options['reference_image_path']);

                if (! empty($options['mask_image_path'])) {
                    $request = $this->attachImage($request, 'mask', $options['mask_image_path']);
                }

                $payload = ['model' => $this->model, 'prompt' => $prompt, 'response_format' => 'b64_json'];
                if ($size) {
                    $payload['size'] = $size;
                }

                $response = $request->post($this->baseUrl.'/v1/images/edits', $payload);
            } else {
                $payload = ['model' => $this->model, 'prompt' => $prompt, 'response_format' => 'b64_json'];
                if ($size) {
                    $payload['size'] = $size;
                }

                $response = $this->http(600)->asJson()->post($this->baseUrl.'/v1/images/generations', $payload);
            }

            if (! $response->successful()) {
                Log::error('OpenAI-compatible image failed: '.$response->status().' - '.$response->body());

                return null;
            }

            return $this->storeResult($response->json('data.0') ?? []);
        } catch (\Throwable $e) {
            Log::error('OpenAI-compatible image exception: '.$e->getMessage());

            return null;
        }
    }

    private function http(int $timeout): PendingRequest
    {
        $request = Http::timeout($timeout)->acceptJson();

        return $this->apiKey ? $request->withToken($this->apiKey) : $request;
    }

    private function attachImage(PendingRequest $request, string $field, string $pathOrUrl): PendingRequest
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            $download = Http::timeout(30)->get($pathOrUrl);

            if (! $download->successful() || $download->body() === '') {
                throw new \RuntimeException('Não foi possível baixar a imagem de referência.');
            }

            return $request->attach($field, $download->body(), basename(parse_url($pathOrUrl, PHP_URL_PATH) ?: '') ?: 'image.png');
        }

        if (! Storage::disk('public')->exists($pathOrUrl)) {
            throw new \RuntimeException("Imagem de referência não encontrada: {$pathOrUrl}");
        }

        return $request->attach($field, Storage::disk('public')->get($pathOrUrl), basename($pathOrUrl));
    }

    private function storeResult(array $item): ?string
    {
        if (! empty($item['b64_json'])) {
            $contents = base64_decode($item['b64_json'], true);

            if ($contents === false) {
                return null;
            }

            $filename = 'generations/openai_'.Str::uuid().'.png';
            Storage::disk('public')->put($filename, $contents);

            return Storage::disk('public')->url($filename);
        }

        if (! empty($item['url'])) {
            return $item['url'];
        }

        Log::error('OpenAI-compatible image: resposta sem imagem', ['item' => $item]);

        return null;
    }
}
