<?php

namespace App\Modules\Yomi\Jobs;

use App\Modules\Yomi\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadMangaCoverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 60, 300];

    public function __construct(public readonly int $midiaId)
    {
        $this->onQueue(config('yomi.queues.media', 'yomi-media'));
    }

    public function handle(MediaService $mediaService): void
    {
        $mediaService->download($this->midiaId);
    }
}
