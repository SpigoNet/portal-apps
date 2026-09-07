<?php

namespace App\Modules\Yomi\Normalizers;

use App\Modules\Yomi\DTOs\ExternalCharacter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\StatusPublicacao;

class AniListNormalizer
{
    public function normalizeMedia(array $media): ExternalManga
    {
        $titles = $media['title'] ?? [];
        $startDate = $this->dateOnly($media['startDate'] ?? null);
        $endDate = $this->dateOnly($media['endDate'] ?? null);
        $externalIds = [];

        if (! empty($media['id'])) {
            $externalIds[] = ['provider' => 'anilist', 'external_id' => (string) $media['id']];
        }

        if (! empty($media['idMal'])) {
            $externalIds[] = ['provider' => 'jikan', 'external_id' => (string) $media['idMal']];
        }

        $score = isset($media['averageScore']) ? round((float) $media['averageScore'] / 10, 2) : null;

        return new ExternalManga(
            title: (string) ($titles['romaji'] ?? $titles['english'] ?? ''),
            originalTitle: ($titles['native'] ?? null) !== null ? (string) $titles['native'] : null,
            synopsis: ($media['description'] ?? null) !== null ? trim(strip_tags((string) $media['description'])) : null,
            status: StatusPublicacao::fromAniList($media['status'] ?? null)->value,
            type: ($media['format'] ?? null) !== null ? strtolower((string) $media['format']) : null,
            chapters: ($media['chapters'] ?? null) !== null ? (int) $media['chapters'] : null,
            volumes: ($media['volumes'] ?? null) !== null ? (int) $media['volumes'] : null,
            publishedFrom: $startDate,
            publishedTo: $endDate,
            score: $score,
            ageRating: null,
            imageUrl: $media['coverImage']['extraLarge'] ?? $media['coverImage']['large'] ?? null,
            sourceUpdatedAt: $endDate,
            externalIds: $externalIds,
            genres: array_map(fn (string $genre): string => $genre, $media['genres'] ?? []),
            alternativeTitles: array_values(array_filter([
                $titles['english'] ?? null,
                $titles['native'] ?? null,
            ])),
            creators: $this->creators($media['staff']['edges'] ?? []),
            characters: $this->characters($media['characters']['edges'] ?? []),
            chapterList: [],
        );
    }

    /**
     * @return array<int, ExternalCreator>
     */
    private function creators(array $edges): array
    {
        return array_map(function (array $edge): ExternalCreator {
            $node = $edge['node'] ?? [];
            $name = $node['name'] ?? [];

            return new ExternalCreator(
                name: (string) ($name['full'] ?? $name['native'] ?? ''),
                originalName: ($name['native'] ?? null) !== null ? (string) $name['native'] : null,
                role: ($edge['role'] ?? null) !== null ? strtolower((string) $edge['role']) : null,
                externalId: isset($node['id']) ? (string) $node['id'] : null,
                imageUrl: $node['image']['large'] ?? null,
            );
        }, $edges);
    }

    /**
     * @return array<int, ExternalCharacter>
     */
    private function characters(array $edges): array
    {
        return array_map(function (array $edge): ExternalCharacter {
            $node = $edge['node'] ?? [];
            $name = $node['name'] ?? [];

            return new ExternalCharacter(
                name: (string) ($name['full'] ?? $name['native'] ?? ''),
                originalName: ($name['native'] ?? null) !== null ? (string) $name['native'] : null,
                role: ($edge['role'] ?? null) !== null ? strtolower((string) $edge['role']) : null,
                description: ($node['description'] ?? null) !== null ? trim(strip_tags((string) $node['description'])) : null,
                externalId: isset($node['id']) ? (string) $node['id'] : null,
                imageUrl: $node['image']['large'] ?? null,
            );
        }, $edges);
    }

    private function dateOnly(?array $date): ?string
    {
        if ($date === null || empty($date['year'])) {
            return null;
        }

        $year = (int) $date['year'];
        $month = (int) ($date['month'] ?? 1);
        $day = (int) ($date['day'] ?? 1);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
