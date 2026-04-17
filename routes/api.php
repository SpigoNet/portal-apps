<?php

use App\Modules\Mithril\Http\Controllers\Api\ContaController;
use App\Modules\Mithril\Http\Controllers\Api\DashboardController;
use App\Modules\Mithril\Http\Controllers\Api\FechamentoController;
use App\Modules\Mithril\Http\Controllers\Api\LancamentoController;
use App\Modules\Mithril\Http\Controllers\Api\PreTransacaoController;
use App\Modules\Mithril\Http\Controllers\Api\RelatorioMarkdownController;
use App\Modules\Mithril\Http\Controllers\Api\TransacaoController;
use App\Modules\Mithril\Http\Middleware\TokenAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('mithril')
    ->name('mithril.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/relatorio-markdown', [RelatorioMarkdownController::class, 'index'])->name('relatorio.markdown');
    });

Route::prefix('mithril')
    ->name('mithril.')
    ->middleware(TokenAuth::class)
    ->group(function () {
        Route::get('/relatorio-markdown', [RelatorioMarkdownController::class, 'index'])->name('relatorio.markdown.token');
        Route::get('/contas', [ContaController::class, 'index'])->name('contas.index');
        Route::post('/contas', [ContaController::class, 'store'])->name('contas.store');
        Route::get('/contas/{id}', [ContaController::class, 'show'])->name('contas.show');
        Route::put('/contas/{id}', [ContaController::class, 'update'])->name('contas.update');
        Route::delete('/contas/{id}', [ContaController::class, 'destroy'])->name('contas.destroy');

        Route::get('/transacoes', [TransacaoController::class, 'index'])->name('transacoes.index');
        Route::post('/transacoes', [TransacaoController::class, 'store'])->name('transacoes.store');
        Route::get('/transacoes/{id}', [TransacaoController::class, 'show'])->name('transacoes.show');
        Route::delete('/transacoes/{id}', [TransacaoController::class, 'destroy'])->name('transacoes.destroy');

        Route::get('/pre-transacoes', [PreTransacaoController::class, 'index'])->name('pre-transacoes.index');
        Route::post('/pre-transacoes', [PreTransacaoController::class, 'store'])->name('pre-transacoes.store');
        Route::get('/pre-transacoes/{id}', [PreTransacaoController::class, 'show'])->name('pre-transacoes.show');
        Route::put('/pre-transacoes/{id}', [PreTransacaoController::class, 'update'])->name('pre-transacoes.update');
        Route::delete('/pre-transacoes/{id}', [PreTransacaoController::class, 'destroy'])->name('pre-transacoes.destroy');
        Route::post('/pre-transacoes/{id}/toggle', [PreTransacaoController::class, 'toggle'])->name('pre-transacoes.toggle');
        Route::post('/pre-transacoes/{id}/efetivar', [PreTransacaoController::class, 'efetivar'])->name('pre-transacoes.efetivar');

        Route::get('/lancamentos', [LancamentoController::class, 'index'])->name('lancamentos.index');

        Route::get('/fechamentos', [FechamentoController::class, 'index'])->name('fechamentos.index');
        Route::post('/fechamentos', [FechamentoController::class, 'store'])->name('fechamentos.store');
    });
