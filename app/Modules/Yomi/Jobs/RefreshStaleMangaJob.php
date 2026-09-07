<?php

namespace App\Modules\Yomi\Jobs;

use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Repositories\MangaRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshStaleMangaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(config('yomi.queues.maintenance', 'yomi-maintenance'));
    }

    public function handle(MangaRepository $mangaRepository): void
    {
        $staleAfter = (int) config('yomi.sync.stale_after_minutes', 1440);
        $batch = (int) config('yomi.sync.refresh_batch_size', 50);

        $mangaRepository->findStale($staleAfter, $batch)
            ->each(fn (Manga $manga) => SyncMangaJob::dispatch($manga->id));
    }
}
