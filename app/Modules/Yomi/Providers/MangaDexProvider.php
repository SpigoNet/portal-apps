<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\MangaDexNormalizer;
use Illuminate\Http\Client\Response;

class MangaDexProvider extends BaseHttpProvider
{
    private const INCLUDES = ['author', 'artist', 'cover_art'];

    public function __construct(protected MangaDexNormalizer $normalizer) {}

    protected function providerName(): ProviderName
    {
        return ProviderName::MangaDex;
    }

    public function health(): array
    {
        return $this->probeHealth('GET', '/manga', [
            'query' => ['limit' => 1],
        ]);
    }

    public function findById(string $id): ?ExternalManga
    {
        $response = $this->request('GET', '/manga/'.$id, [
            'query' => $this->includeQuery(),
        ]);

        if ($response->status() === 404) {
            return null;
        }

        $data = $this->decode($response);
        $item = $data['data'] ?? null;

        if (! is_array($item)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::MangaDex, message: 'Campo "data" ausente na resposta');
        }

        return $this->normalizer->normalizeManga($item);
    }

    public function search(string $query, int $limit = 10): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => $this->queryString([
                'title' => $query,
                'limit' => min($limit, 25),
            ]),
        ]);

        return $this->normalizeItems($this->items($response));
    }

    public function getChapters(string $id): array
    {
        $allChapters = [];
        $offset = 0;
        $limit = 500;
        $maxLoops = 3;

        for ($loop = 0; $loop < $maxLoops; $loop++) {
            $query = 'limit='.$limit.'&offset='.$offset.'&order[chapter]=asc&contentRating[]=safe&contentRating[]=suggestive&contentRating[]=erotica&contentRating[]=pornographic&translatedLanguage[]=pt-br&translatedLanguage[]=en';

            $response = $this->request('GET', '/manga/'.$id.'/feed', [
                'query' => $query,
            ]);

            $items = $this->items($response);

            if (empty($items) && $loop === 0) {
                $queryAny = 'limit='.$limit.'&offset='.$offset.'&order[chapter]=asc&contentRating[]=safe&contentRating[]=suggestive&contentRating[]=erotica&contentRating[]=pornographic';
                $response = $this->request('GET', '/manga/'.$id.'/feed', [
                    'query' => $queryAny,
                ]);
                $items = $this->items($response);
            }

            if (empty($items)) {
                break;
            }

            $normalized = $this->normalizer->normalizeChapters($items);
            $allChapters = array_merge($allChapters, $normalized);

            $total = (int) ($response->json('total') ?? count($items));
            $offset += count($items);

            if ($offset >= $total || count($items) < $limit) {
                break;
            }
        }

        return $allChapters;
    }

    public function topManga(int $limit = 12): array
    {
        return $this->page([
            'order[followedCount]' => 'desc',
            'limit' => min($limit, 25),
        ]);
    }

    public function getRecent(int $limit = 12): array
    {
        return $this->page([
            'order[latestUploadedChapter]' => 'desc',
            'limit' => min($limit, 25),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<int, ExternalManga>
     */
    private function page(array $extra): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => $this->queryString($extra),
        ]);

        return $this->normalizeItems($this->items($response));
    }

    /**
     * Query string com os includes de relações + parâmetros adicionais.
     *
     * @param  array<string, mixed>  $params
     */
    private function queryString(array $params): string
    {
        $base = $this->includeQuery().'&contentRating[]=safe&contentRating[]=suggestive&contentRating[]=erotica&contentRating[]=pornographic';

        if ($params === []) {
            return $base;
        }

        return $base.'&'.http_build_query($params);
    }

    /**
     * Query string com os includes de relações do MangaDex.
     */
    private function includeQuery(): string
    {
        return implode('&', array_map(fn (string $include): string => 'includes[]='.$include, self::INCLUDES));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(Response $response): array
    {
        $data = $this->decode($response);
        $items = $data['data'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::MangaDex, message: 'Campo "data" ausente na resposta');
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ExternalManga>
     */
    private function normalizeItems(array $items): array
    {
        return array_map(fn (array $item): ExternalManga => $this->normalizer->normalizeManga($item), $items);
    }
}
