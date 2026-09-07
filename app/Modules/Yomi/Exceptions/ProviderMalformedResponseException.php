<?php

namespace App\Modules\Yomi\Exceptions;

use App\Modules\Yomi\Enums\ProviderName;

class ProviderMalformedResponseException extends YomiSyncException
{
    public function __construct(
        public readonly ProviderName $provider,
        string $message,
    ) {
        parent::__construct($message);
    }
}
