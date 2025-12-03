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

        Route::get('/contrato/{id}', [ContratoController::class, 'show'])->name('show');
    });
