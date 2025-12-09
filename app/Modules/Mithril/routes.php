<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Mithril\Http\Controllers\FechamentoController;
use App\Modules\Mithril\Http\Controllers\PreTransacaoAcoesController;
use App\Modules\Mithril\Http\Controllers\PreTransacaoController;
use Illuminate\Support\Facades\Route;
use App\Modules\Mithril\Http\Controllers\DashboardController;

Route::prefix('mithril')
    ->name('mithril.')
    ->middleware(['web', 'auth']) // Garante autenticação
    ->middleware(RegistrarAcesso::class . ':Mithril')
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // Placeholders para as próximas rotas (evita erro na View)
        Route::get('/lancamentos', [\App\Modules\Mithril\Http\Controllers\LancamentoController::class, 'index'])->name('lancamentos.index');
        Route::get('/transacao/criar', function() { return 'Em breve'; })->name('transacoes.create');
        Route::get('/fatura/{id}', function($id) { return 'Fatura ' . $id; })->name('faturas.show');

        Route::resource('pre-transacoes', PreTransacaoController::class);
        Route::get('pre-transacoes/{id}/toggle', [PreTransacaoController::class, 'toggleStatus'])->name('pre-transacoes.toggle');

        Route::get('/pre-transacao/{id}/confirmar', [PreTransacaoAcoesController::class, 'showConfirmForm'])->name('pre-transacoes.form-confirmar');
        Route::post('/pre-transacao/{id}/confirmar', [PreTransacaoAcoesController::class, 'confirmar'])->name('pre-transacoes.confirmar');
        Route::post('/pre-transacao/{id}/efetivar', [PreTransacaoAcoesController::class, 'efetivar'])->name('pre-transacoes.efetivar');

        Route::get('/fechamentos', [FechamentoController::class, 'index'])->name('fechamentos.index');
        Route::post('/fechamentos', [FechamentoController::class, 'store'])->name('fechamentos.store');
    });
