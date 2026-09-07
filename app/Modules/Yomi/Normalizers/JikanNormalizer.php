<?php

namespace App\Modules\Yomi\Normalizers;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalCharacter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\StatusPublicacao;
use Carbon\CarbonImmutable;

class JikanNormalizer
{
    public function normalizeManga(array $payload): ExternalManga
    {
        $published = $payload['published'] ?? null;

        return new ExternalManga(
            title: (string) ($payload['title'] ?? ''),
            originalTitle: ($payload['title_japanese'] ?? null) !== null ? (string) $payload['title_japanese'] : null,
            synopsis: ($payload['synopsis'] ?? null) !== null ? (string) $payload['synopsis'] : null,
            status: StatusPublicacao::fromJikan($payload['status'] ?? null)->value,
            type: ($payload['type'] ?? null) !== null ? strtolower((string) $payload['type']) : null,
            chapters: isset($payload['chapters']) ? (int) $payload['chapters'] : null,
            volumes: isset($payload['volumes']) ? (int) $payload['volumes'] : null,
            publishedFrom: $this->dateOnly($published['from'] ?? null),
            publishedTo: $this->dateOnly($published['to'] ?? null),
            score: isset($payload['score']) ? (float) $payload['score'] : null,
            ageRating: ($payload['kids'] ?? false) ? 'kids' : null,
            imageUrl: $this->imageUrl($payload['images'] ?? null),
            sourceUpdatedAt: ($published['to'] ?? null) !== null ? $this->dateOnly($published['to']) : null,
            externalIds: [['provider' => 'jikan', 'external_id' => (string) $payload['mal_id']]],
            genres: array_map(fn (array $genre): string => (string) $genre['name'], $payload['genres'] ?? []),
            alternativeTitles: $this->alternativeTitles($payload),
            creators: $this->creators($payload['authors'] ?? []),
            characters: [],
            chapterList: [],
        );
    }

    /**
     * @return array<int, ExternalCharacter>
     */
    public function normalizeCharacters(array $payload): array
    {
        return array_map(function (array $item): ExternalCharacter {
            $character = $item['character'] ?? [];

            return new ExternalCharacter(
                name: (string) ($character['name'] ?? ''),
                role: ($item['role'] ?? null) !== null ? (string) $item['role'] : null,
                url: ($character['url'] ?? null) !== null ? (string) $character['url'] : null,
                externalId: isset($character['mal_id']) ? (string) $character['mal_id'] : null,
                imageUrl: $this->imageUrl($character['images'] ?? null),
            );
        }, $payload);
    }

    /**
     * @return array<int, ExternalChapter>
     */
    public function normalizeChapters(array $payload): array
    {
        return array_map(function (array $item): ExternalChapter {
            return new ExternalChapter(
                number: (isset($item['chapter']) && $item['chapter'] !== null) ? (float) $item['chapter'] : null,
                title: ($item['title'] ?? null) !== null ? (string) $item['title'] : null,
                externalId: isset($item['mal_id']) ? (string) $item['mal_id'] : null,
                publishedAt: $this->dateOnly($item['published_at'] ?? null),
            );
        }, $payload);
    }

    /**
     * @return array<int, ExternalCreator>
     */
    private function creators(array $authors): array
    {
        return array_map(fn (array $author): ExternalCreator => new ExternalCreator(
            name: (string) ($author['name'] ?? ''),
            role: ($author['type'] ?? null) !== null ? (string) $author['type'] : null,
            url: ($author['url'] ?? null) !== null ? (string) $author['url'] : null,
            externalId: isset($author['mal_id']) ? (string) $author['mal_id'] : null,
        ), $authors);
    }

    private function alternativeTitles(array $payload): array
    {
        $titles = [];

        foreach (['title_english', 'title'] as $key) {
            if (empty($payload[$key])) {
                continue;
            }

            $titles[] = (string) $payload[$key];
        }

        foreach (($payload['title_synonyms'] ?? []) as $synonym) {
            $titles[] = (string) $synonym;
        }

        return array_values(array_unique(array_filter($titles)));
    }

    private function imageUrl(?array $images): ?string
    {
        if ($images === null) {
            return null;
        }

        return $images['jpg']['large_image_url'] ?? $images['webp']['large_image_url'] ?? null;
    }

    private function dateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
