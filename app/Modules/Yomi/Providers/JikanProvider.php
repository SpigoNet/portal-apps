<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\JikanNormalizer;
use Throwable;

class JikanProvider extends BaseHttpProvider
{
    public function __construct(protected JikanNormalizer $normalizer) {}

    protected function providerName(): ProviderName
    {
        return ProviderName::Jikan;
    }

    public function health(): array
    {
        return $this->probeHealth('GET', '/');
    }

    public function findById(string $id): ?ExternalManga
    {
        $response = $this->request('GET', "/manga/{$id}");

        if ($response->status() === 404) {
            return null;
        }

        $data = $this->decode($response);
        $payload = $data['data'] ?? null;

        if (! is_array($payload)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Jikan, message: 'Campo "data" ausente na resposta');
        }

        $external = $this->normalizer->normalizeManga($payload);

        return $this->withCharactersBestEffort($external, $id);
    }

    public function search(string $query, int $limit = 10): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => ['q' => $query, 'limit' => $limit, 'sfw' => true],
        ]);

        $data = $this->decode($response);
        $items = $data['data'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Jikan, message: 'Campo "data" ausente na busca');
        }

        return array_map(fn (array $item): ExternalManga => $this->normalizer->normalizeManga($item), $items);
    }

    public function topManga(int $limit = 12): array
    {
        $response = $this->request('GET', '/top/manga', [
            'query' => ['limit' => min($limit, 25)],
        ]);

        $data = $this->decode($response);
        $items = $data['data'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Jikan, message: 'Campo "data" ausente na listagem de destaques');
        }

        return array_map(fn (array $item): ExternalManga => $this->normalizer->normalizeManga($item), $items);
    }

    public function getRecent(int $limit = 12): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => ['order_by' => 'start_date', 'sort' => 'desc', 'limit' => min($limit, 25), 'sfw' => true],
        ]);

        $data = $this->decode($response);
        $items = $data['data'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Jikan, message: 'Campo "data" ausente na listagem de recentes');
        }

        return array_map(fn (array $item): ExternalManga => $this->normalizer->normalizeManga($item), $items);
    }

    public function getChapters(string $id): array
    {
        try {
            $response = $this->request('GET', "/manga/{$id}/chapters");

            if ($response->status() === 404 || ! $response->successful()) {
                return [];
            }

            $data = $this->decode($response);
            $items = $data['data'] ?? [];

            if (! is_array($items) || empty($items)) {
                return [];
            }

            return $this->normalizer->normalizeChapters($items);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, ExternalChapter>
     */
    private function withCharactersBestEffort(ExternalManga $external, string $id): ExternalManga
    {
        try {
            $response = $this->request('GET', "/manga/{$id}/characters");
            $data = $this->decode($response);
            $items = $data['data'] ?? [];

            if (! is_array($items)) {
                return $external;
            }

            return $external->withCharacters($this->normalizer->normalizeCharacters($items));
        } catch (Throwable) {
            return $external;
        }
    }
}
