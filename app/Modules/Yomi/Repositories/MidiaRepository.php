<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Models\Midia;
use Illuminate\Support\Collection;

class MidiaRepository
{
    public function pendingForRetry(int $limit = 50): Collection
    {
        return Midia::query()
            ->where('status', 'falha')
            ->limit($limit)
            ->get();
    }

    public function markDownloading(Midia $midia): Midia
    {
        $midia->update([
            'status' => 'baixando',
            'attempt' => $midia->attempt + 1,
            'error_message' => null,
        ]);

        return $midia;
    }

    public function markDownloaded(
        Midia $midia,
        string $storagePath,
        string $mimeType,
        ?int $width = null,
        ?int $height = null,
        ?string $checksum = null,
    ): Midia {
        $midia->update([
            'status' => 'baixada',
            'storage_path' => $storagePath,
            'mime_type' => $mimeType,
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
            'downloaded_at' => now(),
            'error_message' => null,
        ]);

        return $midia;
    }

    public function markFailed(Midia $midia, string $errorMessage): Midia
    {
        $midia->update([
            'status' => 'falha',
            'error_message' => $errorMessage,
        ]);

        return $midia;
    }

    public function findByChecksum(string $checksum): ?Midia
    {
        return Midia::query()->where('checksum', $checksum)->first();
    }
}
