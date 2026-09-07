<?php

namespace App\Modules\Yomi\Normalizers;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\StatusPublicacao;
use Carbon\CarbonImmutable;

class KitsuNormalizer
{
    public function normalizeManga(array $data, array $included = []): ExternalManga
    {
        $attributes = $data['attributes'] ?? [];
        $titles = is_array($attributes['titles'] ?? null) ? $attributes['titles'] : [];
        $mainTitle = (string) ($attributes['canonicalTitle'] ?? $titles['en'] ?? '');

        return new ExternalManga(
            title: $mainTitle,
            originalTitle: ($titles['en_jp'] ?? $titles['ja_jp'] ?? null),
            synopsis: ($attributes['synopsis'] ?? null) !== null ? trim(strip_tags((string) $attributes['synopsis'])) : null,
            status: StatusPublicacao::fromKitsu($attributes['status'] ?? null)->value,
            type: ($attributes['subtype'] ?? null) !== null ? strtolower((string) $attributes['subtype']) : null,
            chapters: ($attributes['chapterCount'] ?? null) !== null ? (int) $attributes['chapterCount'] : null,
            volumes: ($attributes['volumeCount'] ?? null) !== null ? (int) $attributes['volumeCount'] : null,
            publishedFrom: $this->dateOnly($attributes['startDate'] ?? null),
            publishedTo: $this->dateOnly($attributes['endDate'] ?? null),
            score: $this->score($attributes['averageRating'] ?? null),
            ageRating: ($attributes['ageRating'] ?? null) !== null ? strtolower((string) $attributes['ageRating']) : null,
            imageUrl: $this->posterImage($attributes['posterImage'] ?? null),
            externalIds: [['provider' => 'kitsu', 'external_id' => (string) ($data['id'] ?? '')]],
            genres: $this->genres($data['relationships']['genres']['data'] ?? [], $included),
            alternativeTitles: $this->alternativeTitles($titles, $mainTitle, $attributes['abbreviatedTitles'] ?? []),
            creators: $this->creators($data['relationships']['staff']['data'] ?? [], $included),
        );
    }

    /**
     * Gêneros vêm na seção "included" da resposta JSON:API; os relacionamentos
     * trazem apenas type/id.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $included
     * @return array<int, string>
     */
    private function genres(array $items, array $included): array
    {
        $genres = [];

        foreach ($items as $item) {
            $genre = $this->findIncluded($included, 'genres', (string) ($item['id'] ?? ''));
            $name = $genre['attributes']['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $genres[] = $name;
        }

        return array_values(array_unique($genres));
    }

    /**
     * Criadores são resolvidos a partir do relacionamento "staff" (mediaStaff),
     * que referencia uma pessoa em "included" por type/id.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $included
     * @return array<int, ExternalCreator>
     */
    private function creators(array $items, array $included): array
    {
        $creators = [];

        foreach ($items as $item) {
            $staff = $this->findIncluded($included, 'mediaStaff', (string) ($item['id'] ?? ''));

            if ($staff === null) {
                continue;
            }

            $role = (string) ($staff['attributes']['role'] ?? 'autor');
            $personId = $staff['relationships']['person']['data']['id'] ?? null;

            if ($personId === null) {
                continue;
            }

            $person = $this->findIncluded($included, 'people', (string) $personId);
            $name = (string) ($person['attributes']['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $creators[] = new ExternalCreator(
                name: $name,
                originalName: null,
                role: $role,
                externalId: (string) $personId,
            );
        }

        return $creators;
    }

    /**
     * @param  array<string, mixed>  $titles
     * @param  array<int, mixed>  $abbreviated
     * @return array<int, string>
     */
    private function alternativeTitles(array $titles, string $mainTitle, array $abbreviated): array
    {
        $alternatives = [];

        foreach ($titles as $title) {
            if (is_string($title) && $title !== '' && $title !== $mainTitle) {
                $alternatives[] = $title;
            }
        }

        foreach ($abbreviated as $title) {
            if (is_string($title) && $title !== '' && $title !== $mainTitle) {
                $alternatives[] = $title;
            }
        }

        return array_values(array_unique($alternatives));
    }

    private function posterImage(?array $image): ?string
    {
        if ($image === null) {
            return null;
        }

        return $image['large'] ?? $image['original'] ?? $image['medium'] ?? null;
    }

    /**
     * Busca um recurso da seção "included" da resposta JSON:API por type/id.
     *
     * @param  array<int, array<string, mixed>>  $included
     * @return array<string, mixed>|null
     */
    private function findIncluded(array $included, string $type, string $id): ?array
    {
        foreach ($included as $item) {
            if (($item['type'] ?? null) === $type && (string) ($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    private function score(?string $rating): ?float
    {
        if ($rating === null || $rating === '') {
            return null;
        }

        $score = (float) $rating;

        if ($score > 10) {
            $score = $score / 10;
        }

        return round($score, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ExternalChapter>
     */
    public function normalizeChapters(array $items): array
    {
        $chapters = [];

        foreach ($items as $item) {
            $attrs = $item['attributes'] ?? [];
            $number = $attrs['number'] ?? null;
            $title = $attrs['canonicalTitle'] ?? null;

            if ($title === null && $number !== null) {
                $title = 'Capítulo '.$number;
            }

            $chapters[] = new ExternalChapter(
                number: $number !== null ? (string) $number : null,
                title: $title,
                externalId: (string) ($item['id'] ?? ''),
                publishedAt: $this->dateOnly($attrs['published'] ?? null),
            );
        }

        return $chapters;
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
