<?php

namespace App\Modules\Yomi\DTOs;

class ExternalChapter
{
    public function __construct(
        public readonly string|float|int|null $number = null,
        public readonly ?string $title = null,
        public readonly ?string $externalId = null,
        public readonly ?string $publishedAt = null,
    ) {}
}
