<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\Contracts\MangaProvider;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Services\YomiSettingsService;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ProviderResolver
{
    private const PROVIDERS = [
        ProviderName::Jikan->value => JikanProvider::class,
        ProviderName::AniList->value => AniListProvider::class,
        ProviderName::Kitsu->value => KitsuProvider::class,
        ProviderName::Mal->value => MalProvider::class,
        ProviderName::MangaDex->value => MangaDexProvider::class,
    ];

    public function __construct(
        protected Container $container,
        protected YomiSettingsService $settings,
    ) {}

    public function resolve(string|ProviderName $provider): MangaProvider
    {
        $name = $provider instanceof ProviderName ? $provider->value : $provider;

        if (! isset(self::PROVIDERS[$name])) {
            throw new InvalidArgumentException("Provedor desconhecido: {$name}");
        }

        return $this->container->make(self::PROVIDERS[$name]);
    }

    /**
     * @return array<int, ProviderName> Provedores habilitados em ordem de prioridade.
     */
    public function priority(): array
    {
        return $this->settings->priority();
    }

    public function isEnabled(ProviderName $provider): bool
    {
        return $this->settings->isEnabled($provider);
    }
}
