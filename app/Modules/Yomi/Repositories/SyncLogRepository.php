<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\SyncOperation;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\SyncLog;

class SyncLogRepository
{
    public function record(
        ?Manga $manga,
        ProviderName $provider,
        SyncOperation $operation,
        string|bool $status,
        ?string $errorType = null,
        ?string $errorMessage = null,
        ?int $httpStatus = null,
        int $attempt = 1,
        ?\Carbon\CarbonInterface $startedAt = null,
        ?\Carbon\CarbonInterface $finishedAt = null,
    ): SyncLog {
        $durationMs = null;

        if ($startedAt !== null && $finishedAt !== null) {
            $durationMs = max(0, (int) $startedAt->diffInMilliseconds($finishedAt));
        }

        return SyncLog::create([
            'manga_id' => $manga?->id,
            'provider' => $provider->value,
            'operation' => $operation->value,
            'status' => is_string($status) ? $status : ($status ? 'sucesso' : 'falha'),
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'http_status' => $httpStatus,
            'attempt' => $attempt,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'duration_ms' => $durationMs,
        ]);
    }
}
