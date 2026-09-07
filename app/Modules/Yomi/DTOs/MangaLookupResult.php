<?php

namespace App\Modules\Yomi\DTOs;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Models\Manga;

class MangaLookupResult
{
    public function __construct(
        public readonly Manga $manga,
        public readonly bool $fromCache = true,
        public readonly ?ProviderName $provider = null,
        public readonly bool $synced = false,
    ) {}
}
