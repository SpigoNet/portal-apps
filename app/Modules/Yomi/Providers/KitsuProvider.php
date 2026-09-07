<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\KitsuNormalizer;
use Throwable;

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

    /**
     * @return array<int, ExternalChapter>
     */
    public function getChapters(string $id): array
    {
        $allChapters = [];
        $offset = 0;
        $limit = 20;

        while (true) {
            try {
                $response = $this->request('GET', '/chapters', [
                    'query' => [
                        'filter[mangaId]' => $id,
                        'page[limit]' => $limit,
                        'page[offset]' => $offset,
                        'sort' => 'number',
                    ],
                ]);
            } catch (Throwable) {
                break;
            }

            if (! $response->successful()) {
                if ($offset === 0) {
                    try {
                        $response = $this->request('GET', '/manga/'.$id.'/chapters', [
                            'query' => [
                                'page[limit]' => $limit,
                                'page[offset]' => $offset,
                                'sort' => 'number',
                            ],
                        ]);
                    } catch (Throwable) {
                        break;
                    }
                }

                if (! $response->successful()) {
                    break;
                }
            }

            $data = $this->decode($response);
            $items = $data['data'] ?? [];

            if (! is_array($items) || empty($items)) {
                break;
            }

            $normalized = $this->normalizer->normalizeChapters($items);
            $allChapters = array_merge($allChapters, $normalized);

            $total = (int) ($data['meta']['count'] ?? count($allChapters));
            $offset += count($items);

            if ($offset >= $total || count($items) < $limit || $offset >= 500) {
                break;
            }
        }

        return $allChapters;
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
