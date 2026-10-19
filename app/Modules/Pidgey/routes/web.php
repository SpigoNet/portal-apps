<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Pidgey\Http\Controllers\AgendamentoController;
use App\Modules\Pidgey\Http\Controllers\ConteudoDinamicoController;
use App\Modules\Pidgey\Http\Controllers\ParametroImagemController;
use App\Modules\Pidgey\Http\Controllers\TelegramVinculoController;
use Illuminate\Support\Facades\Route;

Route::middleware(RegistrarAcesso::class.':Pidgey')
    ->group(function () {
        Route::get('/', [AgendamentoController::class, 'index'])->name('agendamentos.index');
        Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
        Route::get('/agendamentos/{agendamento}/editar', [AgendamentoController::class, 'edit'])->name('agendamentos.edit');
        Route::put('/agendamentos/{agendamento}', [AgendamentoController::class, 'update'])->name('agendamentos.update');
        Route::delete('/agendamentos/{agendamento}', [AgendamentoController::class, 'destroy'])->name('agendamentos.destroy');
        Route::post('/agendamentos/{agendamento}/toggle', [AgendamentoController::class, 'toggle'])->name('agendamentos.toggle');
        Route::post('/agendamentos/{agendamento}/enviar-agora', [AgendamentoController::class, 'enviarAgora'])->name('agendamentos.enviar-agora');

        Route::get('/telegram', [TelegramVinculoController::class, 'index'])->name('telegram.index');
        Route::post('/telegram/codigo', [TelegramVinculoController::class, 'gerarCodigo'])->name('telegram.codigo');
        Route::delete('/telegram', [TelegramVinculoController::class, 'desvincular'])->name('telegram.desvincular');

        Route::get('/conteudos-dinamicos', [ConteudoDinamicoController::class, 'index'])->name('conteudos.index');
        Route::post('/conteudos-dinamicos', [ConteudoDinamicoController::class, 'store'])->name('conteudos.store');
        Route::delete('/conteudos-dinamicos/{conteudo}', [ConteudoDinamicoController::class, 'destroy'])->name('conteudos.destroy');

        // Parâmetros de geração das imagens de personagem, por provedor (valem para todos os envios)
        Route::middleware('admin')->group(function () {
            Route::get('/parametros-imagem', [ParametroImagemController::class, 'index'])->name('parametros-imagem.index');
            Route::put('/parametros-imagem/{provedor}', [ParametroImagemController::class, 'update'])->name('parametros-imagem.update');
        });
    });
