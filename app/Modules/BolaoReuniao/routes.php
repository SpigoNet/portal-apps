<?php

use App\Modules\BolaoReuniao\Http\Controllers\BolaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('bolao')->name('bolao.')->group(function () {
    // Public routes
    Route::get('/', [BolaoController::class, 'index'])->name('index');
    Route::get('/p/{id}', [BolaoController::class, 'participate'])->name('participate');
    Route::post('/guess', [BolaoController::class, 'storeGuess'])->name('guess');
    Route::get('/results/{id}', [BolaoController::class, 'results'])->name('results');
    Route::get('/status/{id}', [BolaoController::class, 'checkStatus'])->name('status');

    // Protected routes (Admin only)
    Route::middleware('auth')->group(function () {
        Route::post('/start', [BolaoController::class, 'start'])->name('start');
        Route::post('/end/{id}', [BolaoController::class, 'end'])->name('end');
    });
});
