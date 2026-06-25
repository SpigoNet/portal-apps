<?php

namespace App\Modules\Alfred\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private string $baseUri;

    private string $apikey;

    private int $timeout;

    public function __construct()
    {
        $this->baseUri = rtrim(config('services.evolution.base_uri', ''), '/');
        $this->apikey = config('services.evolution.token', '');
        $this->timeout = (int) config('services.evolution.timeout', 15);
    }

    public function sendTextToGroup(string $groupJid, string $message): array|bool
    {
        if (empty($this->baseUri) || empty($groupJid)) {
            $msg = 'EvolutionApiService: baseUri or groupJid missing';
            Log::warning($msg, ['base_uri' => $this->baseUri, 'group' => $groupJid]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $msg, 'base_uri' => $this->baseUri, 'endpoint' => null];
        }

        try {
            $endpoint = $this->baseUri.'/message/sendText/'.config('services.evolution.instance', 'Baileys');

            $payload = [
                'number' => $groupJid,
                'text' => $message,
            ];

            $req = Http::timeout($this->timeout)
                ->withHeaders(['apikey' => $this->apikey])
                ->post($endpoint, $payload);

            $status = $req->status();
            $body = $req->body();

            if (! $req->successful()) {
                Log::error('EvolutionApiService sendTextToGroup failed', ['status' => $status, 'body' => $body, 'endpoint' => $endpoint]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed', 'base_uri' => $this->baseUri, 'endpoint' => $endpoint];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null, 'base_uri' => $this->baseUri, 'endpoint' => $endpoint];
        } catch (\Exception $e) {
            Log::error('EvolutionApiService exception', ['error' => $e->getMessage(), 'endpoint' => ($endpoint ?? null)]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage(), 'base_uri' => $this->baseUri, 'endpoint' => ($endpoint ?? null)];
        }
    }
}
