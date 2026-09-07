<?php

namespace App\Modules\Yomi\Normalizers;

use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\StatusPublicacao;
use Carbon\CarbonImmutable;

class MalNormalizer
{
    public function normalizeManga(array $payload): ExternalManga
    {
        return new ExternalManga(
            title: (string) ($payload['title'] ?? ''),
            originalTitle: $payload['alternative_titles']['ja'] ?? null,
            synopsis: ($payload['synopsis'] ?? null) !== null ? trim(strip_tags((string) $payload['synopsis'])) : null,
            status: StatusPublicacao::fromMal($payload['status'] ?? null)->value,
            type: ($payload['media_type'] ?? null) !== null ? strtolower((string) $payload['media_type']) : null,
            chapters: ($payload['num_chapters'] ?? null) !== null ? (int) $payload['num_chapters'] : null,
            volumes: ($payload['num_volumes'] ?? null) !== null ? (int) $payload['num_volumes'] : null,
            publishedFrom: $this->dateOnly($payload['start_date'] ?? null),
            publishedTo: $this->dateOnly($payload['end_date'] ?? null),
            score: ($payload['mean'] ?? null) !== null ? round((float) $payload['mean'], 2) : null,
            ageRating: null,
            imageUrl: $payload['main_picture']['large'] ?? $payload['main_picture']['medium'] ?? null,
            externalIds: [['provider' => 'mal', 'external_id' => (string) $payload['id']]],
            genres: array_map(fn (array $genre): string => (string) $genre['name'], $payload['genres'] ?? []),
            alternativeTitles: $this->alternativeTitles($payload['alternative_titles'] ?? null),
            creators: $this->creators($payload['authors'] ?? []),
        );
    }

    /**
     * @return array<int, ExternalCreator>
     */
    private function creators(array $authors): array
    {
        return array_map(function (array $author): ExternalCreator {
            $node = $author['node'] ?? [];
            $fullName = trim(($node['first_name'] ?? '').' '.($node['last_name'] ?? ''));

            return new ExternalCreator(
                name: $fullName !== '' ? $fullName : (string) ($node['name'] ?? ''),
                originalName: null,
                role: ($author['role'] ?? null) !== null ? strtolower((string) $author['role']) : null,
                externalId: isset($node['id']) ? (string) $node['id'] : null,
            );
        }, $authors);
    }

    /**
     * @return array<int, string>
     */
    private function alternativeTitles(?array $titles): array
    {
        if ($titles === null) {
            return [];
        }

        $alternatives = array_values(array_filter(
            array_map(
                fn (mixed $title): mixed => is_string($title) ? $title : null,
                [$titles['en'] ?? null, $titles['ja'] ?? null],
            ),
        ));

        foreach ($titles['synonyms'] ?? [] as $synonym) {
            $alternatives[] = (string) $synonym;
        }

        return array_values(array_unique(array_filter(array_map('strval', $alternatives))));
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
