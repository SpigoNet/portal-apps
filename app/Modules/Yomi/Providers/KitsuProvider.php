<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\KitsuNormalizer;

class KitsuProvider extends BaseHttpProvider
{
    public function __construct(protected KitsuNormalizer $normalizer) {}

    protected function providerName(): ProviderName
    {
        return ProviderName::Kitsu;
    }

    public function health(): array
    {
        return $this->probeHealth('GET', '/');
    }

    public function findById(string $id): ?ExternalManga
    {
        $response = $this->request('GET', '/manga/'.$id, [
            'query' => ['include' => 'genres,staff.person'],
        ]);

        if ($response->status() === 404) {
            return null;
        }

        $data = $this->decode($response);
        $item = $data['data'] ?? null;

        if (! is_array($item)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Kitsu, message: 'Campo "data" ausente na resposta');
        }

        return $this->normalizer->normalizeManga($item, $data['included'] ?? []);
    }

    public function search(string $query, int $limit = 10): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => [
                'filter[text]' => $query,
                'page[limit]' => min($limit, 20),
                'include' => 'genres',
            ],
        ]);

        return $this->hydrate($this->decode($response));
    }

    public function getChapters(string $id): array
    {
        return [];
    }

    public function topManga(int $limit = 12): array
    {
        return $this->page('manga', ['sort' => '-userCount'], $limit);
    }

    public function getRecent(int $limit = 12): array
    {
        return $this->page('manga', ['sort' => '-createdAt'], $limit);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<int, ExternalManga>
     */
    private function page(string $resource, array $extra, int $limit): array
    {
        $response = $this->request('GET', '/'.$resource, [
            'query' => array_merge($extra, [
                'page[limit]' => min($limit, 20),
                'include' => 'genres',
            ]),
        ]);

        return $this->hydrate($this->decode($response));
    }

    /**
     * Normaliza a lista de obras de uma resposta JSON:API, resolvendo gêneros
     * e criadores a partir da seção "included".
     *
     * @param  array<string, mixed>  $data
     * @return array<int, ExternalManga>
     */
    private function hydrate(array $data): array
    {
        $items = $data['data'] ?? [];
        $included = $data['included'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Kitsu, message: 'Campo "data" ausente na resposta');
        }

        return array_map(fn (array $item): ExternalManga => $this->normalizer->normalizeManga($item, $included), $items);
    }
}
