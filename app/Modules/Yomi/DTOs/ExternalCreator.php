<?php

namespace App\Modules\Yomi\DTOs;

class ExternalCreator
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $originalName = null,
        public readonly ?string $role = null,
        public readonly ?string $url = null,
        public readonly ?string $externalId = null,
        public readonly ?string $imageUrl = null,
    ) {}
}
