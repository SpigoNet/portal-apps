<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Pidgey\Http\Controllers\AgendamentoController;
use App\Modules\Pidgey\Http\Controllers\ConteudoDinamicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(RegistrarAcesso::class.':Pidgey')
    ->group(function () {
        Route::get('/', [AgendamentoController::class, 'index'])->name('agendamentos.index');
        Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
        Route::delete('/agendamentos/{agendamento}', [AgendamentoController::class, 'destroy'])->name('agendamentos.destroy');
        Route::post('/agendamentos/{agendamento}/toggle', [AgendamentoController::class, 'toggle'])->name('agendamentos.toggle');
        Route::post('/agendamentos/{agendamento}/enviar-agora', [AgendamentoController::class, 'enviarAgora'])->name('agendamentos.enviar-agora');

        Route::get('/conteudos-dinamicos', [ConteudoDinamicoController::class, 'index'])->name('conteudos.index');
        Route::post('/conteudos-dinamicos', [ConteudoDinamicoController::class, 'store'])->name('conteudos.store');
        Route::delete('/conteudos-dinamicos/{conteudo}', [ConteudoDinamicoController::class, 'destroy'])->name('conteudos.destroy');
    });
