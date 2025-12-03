<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Metricas\Http\Controllers\MetricasController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;

// Rota para visualizar o Dashboard de Métricas
// Protegida para apenas admins verem
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('metricas-sistema')
    ->name('metricas.')
    ->group(function () {

        // Aplica o middleware de registro nesta própria rota para testar ("Self-tracking")
        Route::get('/', [MetricasController::class, 'index'])
            ->name('index')
            //->middleware(RegistrarAcesso::class . ':Metricas')
        ;

    });
