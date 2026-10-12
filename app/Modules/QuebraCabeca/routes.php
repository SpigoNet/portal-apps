<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\QuebraCabeca\Http\Controllers\QuebraCabecaController;
use Illuminate\Support\Facades\Route;

Route::prefix('quebra-cabeca')
    ->name('quebra-cabeca.')
    ->middleware(['auth', RegistrarAcesso::class.':QuebraCabeca'])
    ->group(function () {
        Route::get('/', [QuebraCabecaController::class, 'index'])->name('index');
        Route::post('/imagens', [QuebraCabecaController::class, 'store'])->name('imagens.store');
        Route::get('/imagens/{imagem}', [QuebraCabecaController::class, 'imagem'])->name('imagens.show');
        Route::delete('/imagens/{imagem}', [QuebraCabecaController::class, 'destroy'])->name('imagens.destroy');
    });
