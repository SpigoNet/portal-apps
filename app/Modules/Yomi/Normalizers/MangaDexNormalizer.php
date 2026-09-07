<?php

namespace App\Modules\Yomi\Normalizers;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\StatusPublicacao;
use Carbon\CarbonImmutable;

class MangaDexNormalizer
{
    private const TITLE_LOCALES = ['en', 'pt-br', 'es', 'fr', 'ja', 'ko', 'zh', 'ru', 'id', 'th', 'vi'];

    public function normalizeManga(array $data): ExternalManga
    {
        $attributes = $data['attributes'] ?? [];
        $titleMap = is_array($attributes['title'] ?? null) ? $attributes['title'] : [];
        $descriptionMap = is_array($attributes['description'] ?? null) ? $attributes['description'] : [];
        $mainTitle = $this->pickLocalized($titleMap);
        $relationships = $data['relationships'] ?? [];

        return new ExternalManga(
            title: $mainTitle,
            originalTitle: $this->originalTitle($titleMap, $mainTitle),
            synopsis: $this->synopsis($descriptionMap),
            status: StatusPublicacao::fromMangaDex($attributes['status'] ?? null)->value,
            type: null,
            chapters: $this->parseCount($attributes['lastChapter'] ?? null),
            volumes: $this->parseCount($attributes['lastVolume'] ?? null),
            publishedFrom: isset($attributes['year']) ? (string) $attributes['year'].'-01-01' : null,
            publishedTo: null,
            score: null,
            ageRating: ($attributes['contentRating'] ?? null) !== null ? (string) $attributes['contentRating'] : null,
            imageUrl: $this->coverUrl($relationships, (string) ($data['id'] ?? '')),
            externalIds: [['provider' => 'mangadex', 'external_id' => (string) ($data['id'] ?? '')]],
            genres: $this->genres($attributes['tags'] ?? []),
            alternativeTitles: $this->alternativeTitles($titleMap, $mainTitle),
            creators: $this->creators($relationships),
        );
    }

    /**
     * @return array<int, ExternalChapter>
     */
    public function normalizeChapters(array $items): array
    {
        return array_map(function (array $item): ExternalChapter {
            $attributes = $item['attributes'] ?? [];

            return new ExternalChapter(
                number: ($attributes['chapter'] ?? null) !== null && $attributes['chapter'] !== ''
                    ? (float) $attributes['chapter']
                    : null,
                title: ($attributes['title'] ?? null) !== null ? (string) $attributes['title'] : null,
                externalId: isset($item['id']) ? (string) $item['id'] : null,
                publishedAt: $this->dateOnly($attributes['publishAt'] ?? null),
            );
        }, $items);
    }

    /**
     * @return array<int, ExternalCreator>
     */
    private function creators(array $relationships): array
    {
        $creators = [];

        foreach ($relationships as $relationship) {
            $type = $relationship['type'] ?? null;

            if (! in_array($type, ['author', 'artist'], true)) {
                continue;
            }

            $name = $relationship['attributes']['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $creators[] = new ExternalCreator(
                name: $name,
                originalName: null,
                role: $type === 'author' ? 'autor' : 'ilustrador',
                externalId: isset($relationship['id']) ? (string) $relationship['id'] : null,
            );
        }

        return $creators;
    }

    /**
     * @return array<int, string>
     */
    private function genres(array $tags): array
    {
        $genres = [];

        foreach ($tags as $tag) {
            $attributes = $tag['attributes'] ?? [];
            $group = $attributes['group'] ?? null;

            if (is_string($group) && $group !== 'genre') {
                continue;
            }

            $name = $attributes['name']['en'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $genres[] = $name;
        }

        return array_values(array_unique($genres));
    }

    private function coverUrl(array $relationships, string $mangaId): ?string
    {
        foreach ($relationships as $relationship) {
            if (($relationship['type'] ?? null) !== 'cover_art') {
                continue;
            }

            $fileName = $relationship['attributes']['fileName'] ?? null;

            if (is_string($fileName) && $fileName !== '') {
                return "https://uploads.mangadex.org/covers/{$mangaId}/{$fileName}.512.jpg";
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function pickLocalized(array $map): string
    {
        foreach (self::TITLE_LOCALES as $locale) {
            if (isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '') {
                return $map[$locale];
            }
        }

        foreach ($map as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function originalTitle(array $map, string $mainTitle): ?string
    {
        foreach (['ja', 'ja-ro', 'ko', 'zh-hk', 'zh'] as $locale) {
            if (isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '' && $map[$locale] !== $mainTitle) {
                return $map[$locale];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<int, string>
     */
    private function alternativeTitles(array $map, string $mainTitle): array
    {
        $alternatives = [];

        foreach ($map as $value) {
            if (is_string($value) && $value !== '' && $value !== $mainTitle) {
                $alternatives[] = $value;
            }
        }

        return array_values(array_unique($alternatives));
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function synopsis(array $map): ?string
    {
        $description = $this->pickLocalized($map);

        if ($description === '') {
            return null;
        }

        return trim(strip_tags($description));
    }

    private function parseCount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = (int) $value;

        return $parsed > 0 ? $parsed : null;
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
