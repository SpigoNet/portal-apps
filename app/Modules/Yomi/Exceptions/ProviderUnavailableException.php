<?php

namespace App\Modules\Yomi\Exceptions;

use App\Modules\Yomi\Enums\ProviderName;

class ProviderUnavailableException extends YomiSyncException
{
    public function __construct(
        public readonly ProviderName $provider,
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $errorType = null,
    ) {
        parent::__construct($message);
    }
}
