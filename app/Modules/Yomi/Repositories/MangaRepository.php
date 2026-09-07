<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Enums\SyncStatus;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\MangaExternalId;
use Illuminate\Support\Collection;

class MangaRepository
{
    public function findStale(int $staleAfterMinutes, int $limit = 50): Collection
    {
        return Manga::query()
            ->where(fn ($query) => $query->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes($staleAfterMinutes)))
            ->limit($limit)
            ->get();
    }

    public function findByExternalId(string $provider, string $externalId): ?Manga
    {
        return Manga::query()
            ->whereHas('externalIds', fn ($query) => $query
                ->where('provider', $provider)
                ->where('external_id', $externalId))
            ->first();
    }

    public function markSyncing(Manga $manga): Manga
    {
        $manga->update([
            'sync_status' => SyncStatus::Sincronizando->value,
        ]);

        return $manga;
    }

    public function markSynced(Manga $manga, ?int $nextSyncAfterMinutes = null): Manga
    {
        $manga->update([
            'sync_status' => SyncStatus::Sincronizado->value,
            'last_synced_at' => now(),
            'next_sync_at' => $nextSyncAfterMinutes !== null
                ? now()->addMinutes($nextSyncAfterMinutes)
                : null,
        ]);

        return $manga;
    }

    public function markFailed(Manga $manga): Manga
    {
        $manga->update([
            'sync_status' => SyncStatus::Falha->value,
        ]);

        return $manga;
    }

    public function attachExternalId(Manga $manga, string $provider, string $externalId): MangaExternalId
    {
        return MangaExternalId::firstOrCreate([
            'manga_id' => $manga->id,
            'provider' => $provider,
        ], [
            'external_id' => $externalId,
        ]);
    }
}
