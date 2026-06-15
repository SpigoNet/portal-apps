<?php

use App\Modules\Bingo\Http\Controllers\BingoController;
use Illuminate\Support\Facades\Route;

Route::prefix('bingo')->name('bingo.')->group(function () {
    Route::get('/', [BingoController::class, 'index'])->name('index');
    Route::get('/criar', [BingoController::class, 'create'])->name('create');
    Route::post('/criar', [BingoController::class, 'store'])->name('store');

    Route::middleware(['auth'])->group(function () {
        Route::get('/historico', [BingoController::class, 'historico'])->name('historico');
    });

    Route::get('/imprimir', [BingoController::class, 'imprimirForm'])->name('imprimir');
    Route::post('/imprimir', [BingoController::class, 'imprimirGerar'])->name('imprimir-gerar');

    Route::get('/temas/{tema}', [BingoController::class, 'temaImagem'])->name('temas')->where('tema', '.*\.(png|jpg|jpeg|gif|webp)');

    Route::get('/{codigo}', [BingoController::class, 'show'])->name('show');
    Route::post('/{codigo}/entrar', [BingoController::class, 'join'])->name('join');
    Route::post('/{codigo}/trocar-cartela', [BingoController::class, 'trocarCartela'])->name('trocar-cartela');
    Route::post('/{codigo}/iniciar', [BingoController::class, 'iniciar'])->name('iniciar');
    Route::post('/{codigo}/sortear', [BingoController::class, 'sortear'])->name('sortear');
    Route::post('/{codigo}/marcar', [BingoController::class, 'marcar'])->name('marcar');
    Route::post('/{codigo}/mensagem', [BingoController::class, 'enviarMensagem'])->name('mensagem');
    Route::post('/{codigo}/declarar-bingo', [BingoController::class, 'declararBingo'])->name('declarar-bingo');
    Route::get('/{codigo}/estado', [BingoController::class, 'estado'])->name('estado');
    Route::get('/{codigo}/resultados', [BingoController::class, 'resultados'])->name('resultados');
    Route::post('/{codigo}/encerrar', [BingoController::class, 'encerrar'])->name('encerrar');
    Route::post('/{codigo}/reiniciar', [BingoController::class, 'reiniciar'])->name('reiniciar');
    Route::post('/{codigo}/resetar', [BingoController::class, 'resetar'])->name('resetar');
});
