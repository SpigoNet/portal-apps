<?php

use App\Modules\Alfred\Http\Controllers\AdminController;
use App\Modules\Alfred\Http\Controllers\AgendamentoController;
use App\Modules\Alfred\Http\Controllers\DashboardController;
use App\Modules\Alfred\Http\Controllers\DiaRuimController;
use App\Modules\Alfred\Http\Controllers\HidratacaoController;
use App\Modules\Alfred\Http\Controllers\MedicamentoController;
use App\Modules\Alfred\Http\Controllers\PersonaController;
use App\Modules\Alfred\Http\Controllers\RotinaController;
use App\Modules\Alfred\Http\Controllers\TarefaController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;

Route::prefix('alfred')
    ->name('alfred.')
    ->middleware(['web', 'auth', RegistrarAcesso::class.':Alfred'])
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::get('/rotinas', [RotinaController::class, 'gerenciar'])->name('rotinas');
            Route::get('/categorias-rotina', [AdminController::class, 'categoriasRotina'])->name('categorias-rotina');
            Route::post('/categorias-rotina', [AdminController::class, 'storeCategoriaRotina'])->name('categorias-rotina.store');
            Route::put('/categorias-rotina/{categoria}', [AdminController::class, 'updateCategoriaRotina'])->name('categorias-rotina.update');
            Route::delete('/categorias-rotina/{categoria}', [AdminController::class, 'destroyCategoriaRotina'])->name('categorias-rotina.destroy');
            Route::get('/configuracoes', [AdminController::class, 'configuracoes'])->name('configuracoes');
            Route::post('/configuracoes', [AdminController::class, 'updateConfiguracoes'])->name('configuracoes.update');
            Route::get('/personas', [PersonaController::class, 'index'])->name('personas.index');
            Route::post('/personas', [PersonaController::class, 'store'])->name('personas.store');
            Route::get('/personas/{persona}', [PersonaController::class, 'show'])->name('personas.show');
            Route::get('/personas/{persona}/edit', [PersonaController::class, 'edit'])->name('personas.edit');
            Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');
            Route::delete('/personas/{persona}', [PersonaController::class, 'destroy'])->name('personas.destroy');
            Route::post('/personas/{persona}/send-test', [PersonaController::class, 'sendTestMessage'])->name('personas.send-test');

            Route::get('/agendamentos', [AgendamentoController::class, 'index'])->name('agendamentos.index');
            Route::get('/agendamentos/criar', [AgendamentoController::class, 'create'])->name('agendamentos.create');
            Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
            Route::get('/agendamentos/{agendamento}/editar', [AgendamentoController::class, 'edit'])->name('agendamentos.edit');
            Route::put('/agendamentos/{agendamento}', [AgendamentoController::class, 'update'])->name('agendamentos.update');
            Route::delete('/agendamentos/{agendamento}', [AgendamentoController::class, 'destroy'])->name('agendamentos.destroy');
            Route::post('/agendamentos/{agendamento}/toggle', [AgendamentoController::class, 'toggle'])->name('agendamentos.toggle');
            Route::post('/agendamentos/{agendamento}/send-test', [AgendamentoController::class, 'sendTest'])->name('agendamentos.send-test');
        });

        Route::get('/tarefas', [TarefaController::class, 'index'])->name('tarefas.index');
        Route::post('/tarefas/{id}/prioridade', [TarefaController::class, 'atualizarPrioridade'])->name('tarefas.atualizar-prioridade');
        Route::post('/tarefas/{id}/status', [TarefaController::class, 'atualizarStatus'])->name('tarefas.atualizar-status');

        Route::resource('medicamentos', MedicamentoController::class);
        Route::post('/medicamentos/{medicamento}/tomar', [MedicamentoController::class, 'tomar'])->name('medicamentos.tomar');
        Route::delete('/medicamentos/{medicamento}/desfazer', [MedicamentoController::class, 'desfazer'])->name('medicamentos.desfazer');

        Route::get('/hidratacao', [HidratacaoController::class, 'index'])->name('hidratacao.index');
        Route::post('/hidratacao', [HidratacaoController::class, 'store'])->name('hidratacao.store');
        Route::post('/hidratacao/registrar-padrao', [HidratacaoController::class, 'registrarPadrao'])->name('hidratacao.registrar-padrao');

        Route::get('/dia-ruim/ativar', [DiaRuimController::class, 'ativar'])->name('dia-ruim.ativar');
        Route::post('/dia-ruim/desativar', [DiaRuimController::class, 'desativar'])->name('dia-ruim.desativar');

        Route::post('/energia/atualizar', [DashboardController::class, 'atualizarEnergia'])->name('energia.atualizar');

        Route::get('/rotinas/calendario/{visualizacao?}', [RotinaController::class, 'calendario'])
            ->name('rotinas.calendario')
            ->where('visualizacao', 'dia|semana|mes');

        Route::resource('rotinas', RotinaController::class);
        Route::post('/rotinas/{rotina}/executar', [RotinaController::class, 'marcarExecutada'])->name('rotinas.marcar-executada');
        Route::delete('/rotinas/{rotina}/desfazer', [RotinaController::class, 'desfazerExecucao'])->name('rotinas.desfazer-execucao');
        Route::post('/rotinas/{rotina}/pular', [RotinaController::class, 'pularHoje'])->name('rotinas.pular-hoje');
        Route::delete('/rotinas/{rotina}/desfazer-pulo', [RotinaController::class, 'desfazerPulo'])->name('rotinas.desfazer-pulo');

        Route::post('/logout', function () {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/');
        })->name('logout');
    });
