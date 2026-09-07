<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Models\YomiSetting;

class YomiSettingsService
{
    private const KEY_PROVIDERS = 'providers';

    private const KEY_PRIORITY = 'provider_priority';

    /**
     * Retorna os provedores habilitados em ordem de prioridade.
     *
     * @return array<int, ProviderName>
     */
    public function priority(): array
    {
        $configured = $this->read(self::KEY_PRIORITY);

        if (! is_array($configured) || $configured === []) {
            $configured = config('yomi.provider_priority', []);
        }

        $priority = [];

        foreach ($configured as $name) {
            $provider = ProviderName::tryFrom((string) $name);

            if ($provider !== null && $this->isEnabled($provider) && ! in_array($provider, $priority, true)) {
                $priority[] = $provider;
            }
        }

        foreach (ProviderName::cases() as $provider) {
            if ($this->isEnabled($provider) && ! in_array($provider, $priority, true)) {
                $priority[] = $provider;
            }
        }

        return $priority;
    }

    /**
     * Nomes dos provedores habilitados em ordem de prioridade.
     *
     * @return array<int, string>
     */
    public function orderedNames(): array
    {
        return array_map(fn (ProviderName $provider): string => $provider->value, $this->priority());
    }

    /**
     * @return array<int, ProviderName>
     */
    public function enabledProviders(): array
    {
        return array_values(array_filter(
            ProviderName::cases(),
            fn (ProviderName $provider): bool => $this->isEnabled($provider),
        ));
    }

    public function isEnabled(ProviderName $provider): bool
    {
        $stored = $this->read(self::KEY_PROVIDERS, []);

        $default = (bool) config('yomi.providers.'.$provider->value.'.enabled', true);

        if (! is_array($stored) || ! array_key_exists($provider->value, $stored)) {
            return $default;
        }

        return (bool) ($stored[$provider->value]['enabled'] ?? $default);
    }

    /**
     * Configuração efetiva de um provedor (merge entre o armazenado e o config/yomi.php).
     *
     * @return array<string, mixed>
     */
    public function providerConfig(ProviderName $provider): array
    {
        $default = (array) config('yomi.providers.'.$provider->value, []);
        $stored = $this->read(self::KEY_PROVIDERS, []);

        $override = is_array($stored) ? ($stored[$provider->value] ?? []) : [];

        $credentials = [];

        foreach (array_keys($provider->credentialFields()) as $key) {
            $credentials[$key] = $this->credentialValue($key, $override, $default);
        }

        return array_merge($default, [
            'enabled' => $this->isEnabled($provider),
            'base_url' => isset($override['base_url']) && trim((string) $override['base_url']) !== ''
                ? (string) $override['base_url']
                : (string) ($default['base_url'] ?? ''),
            'credentials' => $credentials,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function providersConfig(): array
    {
        $providers = [];

        foreach (ProviderName::cases() as $provider) {
            $providers[$provider->value] = $this->providerConfig($provider);
        }

        return $providers;
    }

    /**
     * Dados para preencher o formulário de configuração.
     *
     * @return array{ priority: array<int, string>, providers: array<string, array<string, mixed>> }
     */
    public function form(): array
    {
        return [
            'priority' => $this->orderedNames(),
            'providers' => $this->providersConfig(),
        ];
    }

    /**
     * @param  array{priority: array<int, string>, providers: array<string, array{enabled?: bool, base_url?: string|null, credentials?: array<string, string|null>}>}  $data
     */
    public function save(array $data): void
    {
        $providers = [];

        foreach (ProviderName::cases() as $provider) {
            $config = $data['providers'][$provider->value] ?? [];

            $credentials = [];

            foreach (array_keys($provider->credentialFields()) as $key) {
                $value = $config['credentials'][$key] ?? null;
                $credentials[$key] = is_string($value) && trim($value) !== '' ? trim($value) : null;
            }

            $providers[$provider->value] = [
                'enabled' => (bool) ($config['enabled'] ?? false),
                'base_url' => isset($config['base_url']) && trim((string) $config['base_url']) !== ''
                    ? trim((string) $config['base_url'])
                    : null,
                'credentials' => $credentials,
            ];
        }

        $this->write(self::KEY_PRIORITY, array_unique(array_map('strval', $data['priority'] ?? [])));
        $this->write(self::KEY_PROVIDERS, $providers);
    }

    /**
     * Resolve o valor de uma credencial: armazenada primeiro, config como fallback.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $default
     */
    private function credentialValue(string $key, array $stored, array $default): ?string
    {
        foreach ([$stored, $default] as $source) {
            $value = $source['credentials'][$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function read(string $key, mixed $default = null): mixed
    {
        return YomiSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    public function write(string $key, mixed $value): void
    {
        YomiSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function reset(): void
    {
        YomiSetting::query()->delete();
    }
}
