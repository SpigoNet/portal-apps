<?php

use App\Modules\ComfyQueue\Http\Controllers\DashboardController;
use App\Modules\ComfyQueue\Http\Controllers\WorkerApiController;
use Illuminate\Support\Facades\Route;

// Web Routes
Route::middleware(['web', 'auth'])
    ->prefix('comfy-queue')
    ->name('comfy-queue.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/create', [DashboardController::class, 'create'])->name('create');
        Route::post('/store', [DashboardController::class, 'store'])->name('store');
        Route::get('/assistant', [DashboardController::class, 'assistant'])->name('assistant');
        Route::post('/assistant/store', [DashboardController::class, 'assistantStore'])->name('assistant.store');
        Route::get('/{id}/edit', [DashboardController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DashboardController::class, 'update'])->name('update');
        Route::post('/{id}/requeue', [DashboardController::class, 'requeue'])->name('requeue');
        Route::post('/{id}/duplicate', [DashboardController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{id}', [DashboardController::class, 'destroy'])->name('destroy');
    });

// API Routes para o worker Colab (sem sessão/CSRF, autenticado por X-Api-Key)
Route::prefix('api/comfy-queue')
    ->name('comfy-queue.api.')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/assistant', [WorkerApiController::class, 'assistantCreate'])->name('assistant');
        Route::get('/models', [WorkerApiController::class, 'pendingModels'])->name('models');
        Route::get('/next',   [WorkerApiController::class, 'nextJob'])->name('next');
        Route::get('/job/{id}/done', [WorkerApiController::class, 'done'])->name('job.done.get');
        Route::post('/job/{id}/done', [WorkerApiController::class, 'done'])->name('job.done');
        Route::get('/job/{id}/u', [WorkerApiController::class, 'uploadChunk'])->name('job.upload-short.get');
        Route::post('/job/{id}/u', [WorkerApiController::class, 'uploadChunk'])->name('job.upload-short');
        Route::get('/job/{id}/upload-chunk', [WorkerApiController::class, 'uploadChunk'])->name('job.upload-chunk.get');
        Route::post('/job/{id}/upload-chunk', [WorkerApiController::class, 'uploadChunk'])->name('job.upload-chunk');
        Route::get('/job/{id}/fail', [WorkerApiController::class, 'fail'])->name('job.fail.get');
        Route::post('/job/{id}/fail', [WorkerApiController::class, 'fail'])->name('job.fail');
    });
