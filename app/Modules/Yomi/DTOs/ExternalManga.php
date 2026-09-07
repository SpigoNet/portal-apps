<?php

namespace App\Modules\Yomi\DTOs;

class ExternalManga
{
    /**
     * @param  array<int, array{provider: string, external_id: string}>  $externalIds
     * @param  array<int, string>  $genres
     * @param  array<int, string>  $alternativeTitles
     * @param  array<int, ExternalCreator>  $creators
     * @param  array<int, ExternalCharacter>  $characters
     * @param  array<int, ExternalChapter>  $chapterList
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $originalTitle = null,
        public readonly ?string $synopsis = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?int $chapters = null,
        public readonly ?int $volumes = null,
        public readonly ?string $publishedFrom = null,
        public readonly ?string $publishedTo = null,
        public readonly ?float $score = null,
        public readonly ?string $ageRating = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $sourceUpdatedAt = null,
        public readonly array $externalIds = [],
        public readonly array $genres = [],
        public readonly array $alternativeTitles = [],
        public readonly array $creators = [],
        public readonly array $characters = [],
        public readonly array $chapterList = [],
    ) {}

    /**
     * @param  array<int, ExternalCharacter>  $characters
     */
    public function withCharacters(array $characters): self
    {
        return new self(
            title: $this->title,
            originalTitle: $this->originalTitle,
            synopsis: $this->synopsis,
            status: $this->status,
            type: $this->type,
            chapters: $this->chapters,
            volumes: $this->volumes,
            publishedFrom: $this->publishedFrom,
            publishedTo: $this->publishedTo,
            score: $this->score,
            ageRating: $this->ageRating,
            imageUrl: $this->imageUrl,
            sourceUpdatedAt: $this->sourceUpdatedAt,
            externalIds: $this->externalIds,
            genres: $this->genres,
            alternativeTitles: $this->alternativeTitles,
            creators: $this->creators,
            characters: $characters,
            chapterList: $this->chapterList,
        );
    }
}
