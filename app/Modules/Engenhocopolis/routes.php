<?php

use App\Modules\Engenhocopolis\Http\Controllers\EngenhocopolisController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;

Route::prefix('engenhocopolis')
    ->name('engenhocopolis.')
    ->middleware(['auth', RegistrarAcesso::class.':Engenhocopolis'])
    ->group(function () {
        Route::get('/', [EngenhocopolisController::class, 'index'])->name('index');
    });
