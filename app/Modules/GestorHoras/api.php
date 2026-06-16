<?php

use App\Modules\GestorHoras\Http\Controllers\ApontamentoController;
use App\Modules\Mithril\Http\Middleware\TokenAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('gestor-horas')
    ->name('api.gestor-horas.')
    ->middleware(TokenAuth::class)
    ->group(function () {
        Route::post('/mobile/apontamento/iniciar', [ApontamentoController::class, 'iniciarTimer'])->name('mobile.start');
        Route::post('/mobile/apontamento/salvar-descricao', [ApontamentoController::class, 'salvarDescricao'])->name('mobile.save-desc');
        Route::post('/mobile/apontamento/adicionar-texto', [ApontamentoController::class, 'adicionarTextoDescricao'])->name('mobile.add-text');
        Route::post('/mobile/apontamento/finalizar', [ApontamentoController::class, 'finalizarTimer'])->name('mobile.finish');
    });
