<?php

namespace App\Modules\Yomi\Jobs;

use App\Modules\Yomi\Models\Midia;
use App\Modules\Yomi\Repositories\MidiaRepository;
use App\Modules\Yomi\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(config('yomi.queues.maintenance', 'yomi-maintenance'));
    }

    public function handle(MediaService $mediaService, MidiaRepository $midiaRepository): void
    {
        $midiaRepository->pendingForRetry()
            ->each(fn (Midia $midia) => $mediaService->dispatchDownloadJob($midia));
    }
}
