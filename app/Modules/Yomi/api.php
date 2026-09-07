<?php

use App\Modules\Yomi\Http\Controllers\Api\MangaApiController;
use App\Modules\Yomi\Http\Middleware\TokenAuth;
use Illuminate\Support\Facades\Route;

Route::get('/yomi/api/v1/health', [MangaApiController::class, 'health'])->name('yomi.api.health');

Route::prefix('yomi/api/v1')
    ->name('yomi.api.')
    ->middleware(TokenAuth::class)
    ->group(function () {
        Route::get('/mangas/busca', [MangaApiController::class, 'search'])->name('mangas.busca');
        Route::get('/mangas/populares', [MangaApiController::class, 'populares'])->name('mangas.populares');
        Route::get('/mangas/recentes', [MangaApiController::class, 'recentes'])->name('mangas.recentes');
        Route::get('/mangas/externo/{provider}/{external_id}', [MangaApiController::class, 'externo'])->name('mangas.externo');
        Route::post('/mangas', [MangaApiController::class, 'store'])->name('mangas.store');
        Route::get('/mangas/{id}', [MangaApiController::class, 'show'])->name('mangas.show');
    });
