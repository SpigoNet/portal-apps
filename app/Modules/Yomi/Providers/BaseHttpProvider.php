<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\Contracts\MangaProvider;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Exceptions\ProviderUnavailableException;
use App\Modules\Yomi\Services\YomiSettingsService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class BaseHttpProvider implements MangaProvider
{
    abstract protected function providerName(): ProviderName;

    protected function providerConfig(): array
    {
        return app(YomiSettingsService::class)->providerConfig($this->providerName());
    }

    protected function baseUrl(): string
    {
        return (string) ($this->providerConfig()['base_url'] ?? '');
    }

    /**
     * Headers adicionais enviados em todas as requisições do provedor.
     *
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [];
    }

    /**
     * Valor de uma credencial configurada (via yomi_settings ou config/yomi.php).
     */
    protected function credential(string $key): ?string
    {
        $credentials = $this->providerConfig()['credentials'] ?? [];

        $value = is_array($credentials) ? ($credentials[$key] ?? null) : null;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Executa uma sonda leve de conectividade e monta o resultado de saúde.
     *
     * @return array{provider: string, status: string, latency_ms: int|null, message: string|null}
     */
    protected function probeHealth(string $method, string $path, array $options = []): array
    {
        $startedAt = hrtime(true);

        try {
            $this->request($method, $path, $options);

            return [
                'provider' => $this->providerName()->value,
                'status' => 'ok',
                'latency_ms' => $this->latencyMs($startedAt),
                'message' => 'API respondendo normalmente.',
            ];
        } catch (ProviderUnavailableException $exception) {
            $message = $exception->getMessage();

            if ($exception->httpStatus !== null && $exception->httpStatus >= 400 && $exception->httpStatus < 500) {
                $message = 'Requisição rejeitada pela API (HTTP '.$exception->httpStatus.'). Verifique a configuração e as credenciais.';
            }

            return [
                'provider' => $this->providerName()->value,
                'status' => 'error',
                'latency_ms' => $this->latencyMs($startedAt),
                'message' => $message,
            ];
        }
    }

    private function latencyMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1e6);
    }

    protected function request(string $method, string $path, array $options = []): Response
    {
        $config = $this->providerConfig();
        $timeout = (int) ($config['timeout'] ?? 15);
        $maxAttempts = (int) ($config['retry']['tries'] ?? 3);
        $backoffMs = (int) ($config['retry']['backoff_ms'] ?? 1000);
        $throttleMs = (int) ($config['throttle_ms'] ?? 0);
        $url = $this->baseUrl().$path;

        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($throttleMs > 0) {
                usleep($throttleMs * 1000);
            }

            try {
                $response = Http::timeout($timeout)->withHeaders($this->headers())->send($method, $url, $options);
            } catch (Throwable $throwable) {
                $lastError = new ProviderUnavailableException(
                    provider: $this->providerName(),
                    message: 'Falha de conexão: '.$throwable->getMessage(),
                    errorType: 'connection',
                );

                if ($attempt === $maxAttempts) {
                    throw $lastError;
                }

                usleep($backoffMs * 1000 * (2 ** ($attempt - 1)));

                continue;
            }

            if ($response->status() === 404) {
                return $response;
            }

            if ($response->status() === 429) {
                $lastError = new ProviderUnavailableException(
                    provider: $this->providerName(),
                    message: 'Rate limit atingido no provedor '.$this->providerName()->value,
                    httpStatus: 429,
                    errorType: 'rate_limited',
                );

                if ($attempt === $maxAttempts) {
                    throw $lastError;
                }

                usleep($backoffMs * 1000 * (2 ** ($attempt - 1)));

                continue;
            }

            if ($response->serverError()) {
                $lastError = new ProviderUnavailableException(
                    provider: $this->providerName(),
                    message: "Erro do servidor (HTTP {$response->status()})",
                    httpStatus: $response->status(),
                    errorType: 'server_error',
                );

                if ($attempt === $maxAttempts) {
                    throw $lastError;
                }

                usleep($backoffMs * 1000 * (2 ** ($attempt - 1)));

                continue;
            }

            if (! $response->successful()) {
                throw new ProviderUnavailableException(
                    provider: $this->providerName(),
                    message: "Resposta inesperada (HTTP {$response->status()})",
                    httpStatus: $response->status(),
                    errorType: 'http_error',
                );
            }

            return $response;
        }

        throw $lastError ?? new ProviderUnavailableException(
            provider: $this->providerName(),
            message: 'Provedor indisponível',
            errorType: 'unknown',
        );
    }

    protected function decode(Response $response): array
    {
        try {
            $decoded = $response->json();
        } catch (\JsonException $exception) {
            throw new ProviderMalformedResponseException(
                provider: $this->providerName(),
                message: 'JSON inválido na resposta: '.$exception->getMessage(),
            );
        }

        if (! is_array($decoded)) {
            throw new ProviderMalformedResponseException(
                provider: $this->providerName(),
                message: 'Estrutura de resposta inesperada',
            );
        }

        return $decoded;
    }
}
