<?php

namespace App\Modules\Alfred\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private string $baseUri;

    private int $timeout;

    public function __construct()
    {
        $this->baseUri = rtrim(config('services.evolution.base_uri', 'http://192.168.15.10:8099'), '/');
        $this->timeout = (int) config('services.evolution.timeout', 15);
    }

    public function sendTextToGroup(string $groupJid, string $message): array
    {
        if (empty($this->baseUri) || empty($groupJid)) {
            $msg = 'WhatsApp API: baseUri or groupJid missing';
            Log::warning($msg, ['base_uri' => $this->baseUri, 'group' => $groupJid]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $msg];
        }

        try {
            $endpoint = $this->baseUri.'/send';

            $payload = [
                'number' => $groupJid,
                'message' => $message,
            ];

            $req = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, $payload);

            $status = $req->status();
            $body = $req->body();

            if (! $req->successful()) {
                Log::error('WhatsApp API send failed', ['status' => $status, 'body' => $body, 'endpoint' => $endpoint]);

                return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => 'request_failed'];
            }

            return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
        } catch (\Exception $e) {
            Log::error('WhatsApp API exception', ['error' => $e->getMessage(), 'endpoint' => ($endpoint ?? null)]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }
}
