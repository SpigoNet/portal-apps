<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Terminal\Http\Controllers\TerminalController;
use Illuminate\Support\Facades\Route;

Route::prefix('terminal')
    ->name('terminal.')
    ->middleware(['auth', RegistrarAcesso::class.':Terminal'])
    ->group(function () {
        Route::get('/', [TerminalController::class, 'index'])->name('index');
        Route::get('/ranking', [TerminalController::class, 'ranking'])->name('ranking');
        Route::post('/resultado', [TerminalController::class, 'registrar'])->name('registrar');
        Route::get('/diario', [TerminalController::class, 'diario'])->name('diario');
        Route::post('/diario', [TerminalController::class, 'registrarDiario'])->name('diario.registrar');
        Route::post('/conquista', [TerminalController::class, 'registrarConquista'])->name('conquista');
    });
