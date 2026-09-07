<?php

namespace App\Modules\Yomi\Jobs;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Services\MangaSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMangaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public readonly int $mangaId, public readonly ?ProviderName $provider = null)
    {
        $this->onQueue(config('yomi.queues.sync', 'yomi-sync'));
    }

    public function handle(MangaSyncService $mangaSyncService): void
    {
        $mangaSyncService->sync($this->mangaId, $this->provider);
    }
}
