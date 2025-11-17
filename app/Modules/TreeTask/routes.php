<?php

use App\Modules\TreeTask\Http\Controllers\AnexoController;
use App\Modules\TreeTask\Http\Controllers\FocusController;
use Illuminate\Support\Facades\Route;
use App\Modules\TreeTask\Http\Controllers\ProjetoController;
use App\Modules\TreeTask\Http\Controllers\FaseController;
use App\Modules\TreeTask\Http\Controllers\TarefaController;

Route::prefix('treetask')
    ->name('treetask.')
    ->middleware(['web', 'auth'])
    ->group(function () {

        // --- Projetos ---
        Route::get('/', [ProjetoController::class, 'index'])->name('index');
        Route::get('/criar', [ProjetoController::class, 'create'])->name('create');
        Route::post('/', [ProjetoController::class, 'store'])->name('store');
        Route::get('/projeto/{id}', [ProjetoController::class, 'show'])->name('show');

        // --- Fases ---
        Route::post('/fases', [FaseController::class, 'store'])->name('fases.store');
        // Rota para deletar fase (opcional por enquanto)
        Route::delete('/fases/{id}', [FaseController::class, 'destroy'])->name('fases.destroy');

        // --- Tarefas ---
        // Exibe form de criar tarefa, já vinculada a uma fase específica
        Route::get('/fases/{id_fase}/tarefa/criar', [TarefaController::class, 'create'])->name('tarefas.create');
        Route::post('/tarefas', [TarefaController::class, 'store'])->name('tarefas.store');
        Route::get('/tarefas/{id}', [TarefaController::class, 'show'])->name('tarefas.show');

        Route::get('/tarefas/{id}/editar', [TarefaController::class, 'edit'])->name('tarefas.edit');
        Route::put('/tarefas/{id}', [TarefaController::class, 'update'])->name('tarefas.update');

        // Rotas de Anexos
        Route::post('/tarefas/{id}/anexos', [AnexoController::class, 'store'])->name('anexos.store');
        Route::get('/anexos/{id}/download', [AnexoController::class, 'download'])->name('anexos.download');
        Route::delete('/tarefas/{taskId}/anexos/{anexoId}', [AnexoController::class, 'destroy'])->name('anexos.destroy');


        // ... dentro do grupo de rotas
        Route::get('/meu-foco', [FocusController::class, 'index'])->name('focus.index');

        // Nova rota para alterar APENAS o status
        Route::patch('/tarefas/{id}/status', [TarefaController::class, 'updateStatus'])->name('tarefas.updateStatus');
    });
