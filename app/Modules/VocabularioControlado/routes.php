<?php

use App\Modules\VocabularioControlado\Http\Controllers\AdminController;
use App\Modules\VocabularioControlado\Http\Controllers\PdfController;
use App\Modules\VocabularioControlado\Http\Controllers\PesquisaController;
use App\Modules\VocabularioControlado\Http\Controllers\SolicitacaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('vocabulario-controlado')->name('vocabulario-controlado.')->group(function () {

    // Pesquisa pública — GET com ?palavra= para busca inline, sem iframe
    Route::get('/', [PesquisaController::class, 'index'])->name('index');

    // PDF para download / impressão
    Route::get('/pdf', [PdfController::class, 'index'])->name('pdf');

    // Portal de solicitação (identificado por ?mail= e ?nome=)
    Route::get('/solicitacao', [SolicitacaoController::class, 'index'])->name('solicitacao');
    Route::get('/solicitacao/lista-fragmento', [SolicitacaoController::class, 'listaFragmento'])->name('solicitacao.lista-fragmento');
    Route::post('/solicitacao', [SolicitacaoController::class, 'store'])->name('solicitacao.store');
    Route::post('/solicitacao/aprovar', [SolicitacaoController::class, 'aprovar'])->name('solicitacao.aprovar');
    Route::post('/solicitacao/implantacao', [SolicitacaoController::class, 'implantacao'])->name('solicitacao.implantacao');

    // Admin — protegido por auth do portal
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/criar', [AdminController::class, 'criar'])->name('criar');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
        Route::get('/listas', [AdminController::class, 'listas'])->name('listas');
    });
});
