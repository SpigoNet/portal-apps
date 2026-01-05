<?php

use App\Modules\MundosDeMim\Http\Controllers\EstilosController;
use App\Modules\MundosDeMim\Http\Controllers\GaleriaController;
use Illuminate\Support\Facades\Route;
use App\Modules\MundosDeMim\Http\Controllers\DashboardController; // <--- Importar
use App\Modules\MundosDeMim\Http\Controllers\PerfilBiometricoController;
use App\Modules\MundosDeMim\Http\Controllers\PessoasRelacionadasController;

Route::prefix('mundos-de-mim')
    ->name('mundos-de-mim.')
    ->group(function () {

        // Rota Principal (Dashboard do Módulo)
        Route::get('/', [DashboardController::class, 'index'])->name('index'); // <--- Nova Rota

        // Rotas de Biometria
        Route::get('/meu-perfil', [PerfilBiometricoController::class, 'index'])->name('perfil.index');
        Route::post('/meu-perfil', [PerfilBiometricoController::class, 'update'])->name('perfil.update');

        // Rotas de Pessoas
        Route::get('/pessoas', [PessoasRelacionadasController::class, 'index'])->name('pessoas.index');
        Route::get('/pessoas/adicionar', [PessoasRelacionadasController::class, 'create'])->name('pessoas.create');
        Route::post('/pessoas', [PessoasRelacionadasController::class, 'store'])->name('pessoas.store');
        Route::post('/pessoas/{id}/toggle', [PessoasRelacionadasController::class, 'toggleActive'])->name('pessoas.toggle');

        // Rota da Galeria
        Route::get('/galeria', [GaleriaController::class, 'index'])->name('galeria.index');

        // Rota de Estilos
        Route::get('/estilos', [EstilosController::class, 'index'])->name('estilos.index');
    });
