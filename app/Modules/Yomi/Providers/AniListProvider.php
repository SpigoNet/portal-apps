<?php

namespace App\Modules\Yomi\Providers;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Normalizers\AniListNormalizer;

class AniListProvider extends BaseHttpProvider
{
    protected function providerName(): ProviderName
    {
        return ProviderName::AniList;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $token = $this->credential('token');

        if ($token === null) {
            return [];
        }

        return ['Authorization' => 'Bearer '.$token];
    }

    public function health(): array
    {
        return $this->probeHealth('POST', '/', [
            'json' => [
                'query' => 'query { Page(perPage: 1) { media(type: MANGA) { id } } }',
            ],
        ]);
    }

    public function __construct(protected AniListNormalizer $normalizer) {}

    public function findById(string $id): ?ExternalManga
    {
        if (str_starts_with($id, 'mal:')) {
            $variable = 'idMal';
            $value = (int) substr($id, 4);
            $query = $this->mediaByMalQuery();
        } else {
            $variable = 'id';
            $value = (int) $id;
            $query = $this->mediaByIdQuery();
        }

        $response = $this->request('POST', '/', [
            'json' => [
                'query' => $query,
                'variables' => [$variable => $value],
            ],
        ]);

        $data = $this->decode($response);
        $this->assertNoErrors($data);

        $media = $data['data']['Media'] ?? null;

        if (! is_array($media)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::AniList, message: 'Campo "Media" ausente na resposta');
        }

        return $this->normalizer->normalizeMedia($media);
    }

    public function search(string $query, int $perPage = 10): array
    {
        $response = $this->request('POST', '/', [
            'json' => [
                'query' => <<<'GRAPHQL'
                    query ($search: String, $perPage: Int) {
                        Page(page: 1, perPage: $perPage) {
                            media(search: $search, type: MANGA) {
                                id
                                idMal
                                title {
                                    romaji
                                    english
                                    native
                                }
                                description(asHtml: false)
                                status
                                format
                                chapters
                                volumes
                                startDate { year month day }
                                endDate { year month day }
                                genres
                                averageScore
                                coverImage {
                                    extraLarge
                                    large
                                }
                                staff {
                                    edges {
                                        role
                                        node {
                                            id
                                            name {
                                                full
                                                native
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    GRAPHQL,
                'variables' => ['search' => $query, 'perPage' => $perPage],
            ],
        ]);

        $data = $this->decode($response);
        $this->assertNoErrors($data);

        $mediaList = $data['data']['Page']['media'] ?? [];

        if (! is_array($mediaList)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::AniList, message: 'Campo "Page" ausente na busca');
        }

        return array_map(fn (array $media): ExternalManga => $this->normalizer->normalizeMedia($media), $mediaList);
    }

    public function getChapters(string $id): array
    {
        // A API do AniList não expõe a listagem granular de capítulos.
        return [];
    }

    public function topManga(int $perPage = 12): array
    {
        return $this->pageBySort('POPULARITY_DESC', $perPage);
    }

    public function getRecent(int $limit = 12): array
    {
        return $this->pageBySort('START_DATE_DESC', $limit);
    }

    /**
     * Executa uma consulta de página de mangás ordenada por um critério do AniList.
     *
     * @return array<int, ExternalManga>
     */
    private function pageBySort(string $sort, int $perPage): array
    {
        $response = $this->request('POST', '/', [
            'json' => [
                'query' => $this->pageQuery($sort),
                'variables' => ['perPage' => $perPage],
            ],
        ]);

        $data = $this->decode($response);
        $this->assertNoErrors($data);

        $mediaList = $data['data']['Page']['media'] ?? [];

        if (! is_array($mediaList)) {
            throw new ProviderMalformedResponseException(provider: ProviderName::AniList, message: 'Campo "Page" ausente na listagem');
        }

        return array_map(fn (array $media): ExternalManga => $this->normalizer->normalizeMedia($media), $mediaList);
    }

    private function pageQuery(string $sort): string
    {
        return <<<GRAPHQL
            query (\$perPage: Int) {
                Page(page: 1, perPage: \$perPage) {
                    media(type: MANGA, sort: $sort) {
                        id
                        idMal
                        title {
                            romaji
                            english
                            native
                        }
                        description(asHtml: false)
                        status
                        format
                        chapters
                        volumes
                        startDate { year month day }
                        endDate { year month day }
                        genres
                        averageScore
                        coverImage {
                            extraLarge
                            large
                        }
                        staff {
                            edges {
                                role
                                node {
                                    id
                                    name {
                                        full
                                        native
                                    }
                                    image {
                                        large
                                    }
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;
    }

    private function mediaByIdQuery(): string
    {
        return <<<'GRAPHQL'
            query ($id: Int) {
                Media(id: $id, type: MANGA) {
                    id
                    idMal
                    title {
                        romaji
                        english
                        native
                    }
                    description(asHtml: false)
                    status
                    format
                    chapters
                    volumes
                    startDate { year month day }
                    endDate { year month day }
                    genres
                    averageScore
                    coverImage {
                        extraLarge
                        large
                    }
                    staff {
                        edges {
                            role
                            node {
                                id
                                name {
                                    full
                                    native
                                }
                                image {
                                    large
                                }
                            }
                        }
                    }
                    characters {
                        edges {
                            role
                            node {
                                id
                                name {
                                    full
                                    native
                                }
                                description(asHtml: false)
                                image {
                                    large
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;
    }

    private function mediaByMalQuery(): string
    {
        return <<<'GRAPHQL'
            query ($idMal: Int) {
                Media(idMal: $idMal, type: MANGA) {
                    id
                    idMal
                    title {
                        romaji
                        english
                        native
                    }
                    description(asHtml: false)
                    status
                    format
                    chapters
                    volumes
                    startDate { year month day }
                    endDate { year month day }
                    genres
                    averageScore
                    coverImage {
                        extraLarge
                        large
                    }
                    staff {
                        edges {
                            role
                            node {
                                id
                                name {
                                    full
                                    native
                                }
                                image {
                                    large
                                }
                            }
                        }
                    }
                    characters {
                        edges {
                            role
                            node {
                                id
                                name {
                                    full
                                    native
                                }
                                description(asHtml: false)
                                image {
                                    large
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;
    }

    private function assertNoErrors(array $data): void
    {
        $errors = $data['errors'] ?? [];

        if (! is_array($errors) || $errors === []) {
            return;
        }

        $status = (int) ($errors[0]['status'] ?? 0);

        if ($status === 404) {
            throw new ProviderMalformedResponseException(provider: ProviderName::AniList, message: 'Obra não encontrada no AniList');
        }

        $message = (string) ($errors[0]['message'] ?? 'Erro GraphQL desconhecido');

        throw new ProviderMalformedResponseException(provider: ProviderName::AniList, message: $message);
    }
}
