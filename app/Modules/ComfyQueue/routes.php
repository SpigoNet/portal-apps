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
    });

// API Routes para o worker Colab (sem sessão/CSRF, autenticado por X-Api-Key)
Route::prefix('api/comfy-queue')
    ->name('comfy-queue.api.')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/models', [WorkerApiController::class, 'pendingModels'])->name('models');
        Route::get('/next',   [WorkerApiController::class, 'nextJob'])->name('next');
        Route::get('/job/{id}/done', [WorkerApiController::class, 'done'])->name('job.done.get');
        Route::post('/job/{id}/done', [WorkerApiController::class, 'done'])->name('job.done');
        Route::post('/job/{id}/upload-chunk', [WorkerApiController::class, 'uploadChunk'])->name('job.upload-chunk');
        Route::get('/job/{id}/fail', [WorkerApiController::class, 'fail'])->name('job.fail.get');
        Route::post('/job/{id}/fail', [WorkerApiController::class, 'fail'])->name('job.fail');
    });
