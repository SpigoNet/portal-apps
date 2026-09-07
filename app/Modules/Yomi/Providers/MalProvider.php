<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\MalNormalizer;
use Illuminate\Http\Client\Response;

class MalProvider extends BaseHttpProvider
{
    private const FIELDS = 'id,title,main_picture,alternative_titles,synopsis,mean,status,media_type,num_chapters,num_volumes,start_date,end_date,genres,authors';

    public function __construct(protected MalNormalizer $normalizer) {}

    protected function providerName(): ProviderName
    {
        return ProviderName::Mal;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $clientId = $this->credential('client_id');

        if ($clientId === null) {
            return [];
        }

        return ['X-MAL-CLIENT-ID' => $clientId];
    }

    public function health(): array
    {
        if ($this->credential('client_id') === null) {
            return [
                'provider' => ProviderName::Mal->value,
                'status' => 'not_configured',
                'latency_ms' => null,
                'message' => 'Client ID da API do MyAnimeList não informado nas configurações.',
            ];
        }

        return $this->probeHealth('GET', '/manga/1', [
            'query' => ['fields' => 'id'],
        ]);
    }

    public function findById(string $id): ?ExternalManga
    {
        $response = $this->request('GET', '/manga/'.$id, [
            'query' => ['fields' => self::FIELDS],
        ]);

        if ($response->status() === 404) {
            return null;
        }

        $data = $this->decode($response);

        if (! is_array($data) || ! isset($data['id'])) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Mal, message: 'Estrutura de resposta inesperada');
        }

        return $this->normalizer->normalizeManga($data);
    }

    public function search(string $query, int $limit = 10): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => [
                'q' => $query,
                'limit' => min($limit, 25),
                'fields' => self::FIELDS,
            ],
        ]);

        return $this->normalizeNodes($this->nodes($response));
    }

    public function getChapters(string $id): array
    {
        return [];
    }

    public function topManga(int $limit = 12): array
    {
        $response = $this->request('GET', '/manga/ranking', [
            'query' => [
                'ranking_type' => 'all',
                'limit' => min($limit, 25),
                'fields' => self::FIELDS,
            ],
        ]);

        return $this->normalizeNodes($this->nodes($response));
    }

    public function getRecent(int $limit = 12): array
    {
        $response = $this->request('GET', '/manga', [
            'query' => [
                'order_by' => 'start_date',
                'sort' => 'desc',
                'limit' => min($limit, 25),
                'fields' => self::FIELDS,
            ],
        ]);

        return $this->normalizeNodes($this->nodes($response));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nodes(Response $response): array
    {
        $data = $this->decode($response);
        $items = $data['data'] ?? [];

        if (! is_array($items)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::Mal, message: 'Campo "data" ausente na resposta');
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ExternalManga>
     */
    private function normalizeNodes(array $items): array
    {
        return array_map(function (array $item): ExternalManga {
            $node = $item['node'] ?? $item;

            if (! is_array($node)) {
                throw new ProviderMalformedResponseException(provider: ProviderName::Mal, message: 'Campo "node" ausente na resposta');
            }

            return $this->normalizer->normalizeManga($node);
        }, $items);
    }
}
