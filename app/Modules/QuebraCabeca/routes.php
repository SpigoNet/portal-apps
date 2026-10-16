<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\QuebraCabeca\Http\Controllers\QuebraCabecaController;
use Illuminate\Support\Facades\Route;

Route::prefix('quebra-cabeca')
    ->name('quebra-cabeca.')
    ->middleware(['auth', RegistrarAcesso::class.':QuebraCabeca'])
    ->group(function () {
        Route::get('/', [QuebraCabecaController::class, 'index'])->name('index');
        Route::get('/padrao/{arquivo}', [QuebraCabecaController::class, 'padrao'])->name('padrao')->where('arquivo', '[A-Za-z0-9._-]+');
        Route::post('/imagens', [QuebraCabecaController::class, 'store'])->name('imagens.store');
        Route::get('/imagens/{imagem}', [QuebraCabecaController::class, 'imagem'])->name('imagens.show');
        Route::patch('/imagens/{imagem}', [QuebraCabecaController::class, 'update'])->name('imagens.update');
        Route::delete('/imagens/{imagem}', [QuebraCabecaController::class, 'destroy'])->name('imagens.destroy');
        Route::post('/grupos', [QuebraCabecaController::class, 'storeGrupo'])->name('grupos.store');
        Route::delete('/grupos/{grupo}', [QuebraCabecaController::class, 'destroyGrupo'])->name('grupos.destroy');
    });
