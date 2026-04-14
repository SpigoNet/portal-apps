<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;
use App\Modules\GestorHoras\Http\Controllers\ContratoController;
use App\Modules\GestorHoras\Http\Controllers\ApontamentoController;

Route::get('/acompanhamento/{token}', [ContratoController::class, 'publicView'])
    ->name('gestor-horas.publico');

Route::middleware(['web', 'auth'])
    ->prefix('gestor-horas')
    ->name('gestor-horas.')
    ->middleware(RegistrarAcesso::class . ':GestorHoras')
    ->group(function () {

        // Listagem (Dashboard) - Acesso controlado no Controller
        Route::get('/', [ContratoController::class, 'index'])->name('index');

        // Rotas de Criação de Contrato (Protegidas por Gate no Controller)
        Route::get('/novo-contrato', [ContratoController::class, 'create'])->name('create');
        Route::post('/novo-contrato', [ContratoController::class, 'store'])->name('store');

        // Rota para Lançar Horas
        Route::post('/contrato/{id}/apontar', [ApontamentoController::class, 'store'])->name('apontar');
        Route::post('/contrato/{id}/separar-faturamento', [ApontamentoController::class, 'separarParaFaturamento'])->name('separar-faturamento');
        Route::post('/contrato/{id}/faturamento-status', [ApontamentoController::class, 'atualizarStatusFaturamento'])->name('faturamento-status');

        Route::get('/mobile/apontamento', [ApontamentoController::class, 'mobileTimer'])->name('mobile.timer');
        Route::post('/mobile/apontamento/iniciar', [ApontamentoController::class, 'iniciarTimer'])->name('mobile.start');
        Route::post('/mobile/apontamento/salvar-descricao', [ApontamentoController::class, 'salvarDescricao'])->name('mobile.save-desc');
        Route::post('/mobile/apontamento/finalizar', [ApontamentoController::class, 'finalizarTimer'])->name('mobile.finish');

        Route::get('/contrato/{id}', [ContratoController::class, 'show'])->name('show');
    });
