<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Pidgey\Http\Controllers\AgendamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(RegistrarAcesso::class.':Pidgey')
    ->group(function () {
        Route::get('/', [AgendamentoController::class, 'index'])->name('agendamentos.index');
        Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
        Route::delete('/agendamentos/{agendamento}', [AgendamentoController::class, 'destroy'])->name('agendamentos.destroy');
        Route::post('/agendamentos/{agendamento}/toggle', [AgendamentoController::class, 'toggle'])->name('agendamentos.toggle');
    });
