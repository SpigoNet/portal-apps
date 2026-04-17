<?php

use App\Modules\VocabularioControlado\Http\Controllers\AdminController;
use App\Modules\VocabularioControlado\Http\Controllers\PesquisaController;
use App\Modules\VocabularioControlado\Http\Controllers\SolicitacaoController;
use Illuminate\Support\Facades\Route;

// Rotas públicas / sem autenticação Laravel (acesso via iframe ou link externo)
Route::prefix('vocabulario-controlado')->name('vocabulario-controlado.')->group(function () {

    // Pesquisa pública de termos
    Route::get('/', [PesquisaController::class, 'index'])->name('index');
    Route::post('/buscar', [PesquisaController::class, 'buscar'])->name('buscar');

    // Portal de solicitação (identificado por ?mail=)
    // Pode ser chamado sem parâmetros (modo anônimo/somente leitura) ou com ?mail=&nome=
    Route::get('/solicitacao', [SolicitacaoController::class, 'index'])->name('solicitacao');
    Route::post('/solicitacao', [SolicitacaoController::class, 'store'])->name('solicitacao.store');
    Route::post('/solicitacao/aprovar', [SolicitacaoController::class, 'aprovar'])->name('solicitacao.aprovar');
    Route::post('/solicitacao/implantacao', [SolicitacaoController::class, 'implantacao'])->name('solicitacao.implantacao');

    // Admin — protegido por auth + admin do portal
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/criar', [AdminController::class, 'criar'])->name('criar');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
        Route::get('/listas', [AdminController::class, 'listas'])->name('listas');
    });
});
