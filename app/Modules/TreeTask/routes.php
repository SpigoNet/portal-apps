<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\TreeTask\Http\Controllers\AiCommandController;
use App\Modules\TreeTask\Http\Controllers\AnexoController;
use App\Modules\TreeTask\Http\Controllers\CelebrationController;
use App\Modules\TreeTask\Http\Controllers\FocusController;
use App\Modules\TreeTask\Http\Controllers\GamificationController; // <--- Ensure this is imported
use App\Modules\TreeTask\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Modules\TreeTask\Http\Controllers\ProjetoController;
use App\Modules\TreeTask\Http\Controllers\FaseController;
use App\Modules\TreeTask\Http\Controllers\TarefaController;
use Illuminate\Support\Facades\Artisan;

Route::prefix('treetask')
    ->name('treetask.')
    ->middleware(['web', 'auth'])
    ->middleware(RegistrarAcesso::class . ':TreeTask')
    ->group(function () {

        // --- Projetos ---
        Route::get('/', [ProjetoController::class, 'index'])->name('index');
        Route::get('/criar', [ProjetoController::class, 'create'])->name('create');
        Route::post('/', [ProjetoController::class, 'store'])->name('store');
        Route::get('/projeto/{id}', [ProjetoController::class, 'show'])->name('show');

        // --- Fases ---
        Route::post('/fases', [FaseController::class, 'store'])->name('fases.store');
        Route::delete('/fases/{id}', [FaseController::class, 'destroy'])->name('fases.destroy');

        // --- Tarefas ---
        Route::get('/fases/{id_fase}/tarefa/criar', [TarefaController::class, 'create'])->name('tarefas.create');
        Route::post('/tarefas', [TarefaController::class, 'store'])->name('tarefas.store');
        Route::get('/tarefas/{id}', [TarefaController::class, 'show'])->name('tarefas.show');
        Route::get('/tarefas/{id}/editar', [TarefaController::class, 'edit'])->name('tarefas.edit');
        Route::put('/tarefas/{id}', [TarefaController::class, 'update'])->name('tarefas.update');
        Route::patch('/tarefas/{id}/status', [TarefaController::class, 'updateStatus'])->name('tarefas.updateStatus');

        // Rotas de Anexos
        Route::post('/tarefas/{id}/anexos', [AnexoController::class, 'store'])->name('anexos.store');
        Route::get('/anexos/{id}/download', [AnexoController::class, 'download'])->name('anexos.download');
        Route::delete('/tarefas/{taskId}/anexos/{anexoId}', [AnexoController::class, 'destroy'])->name('anexos.destroy');

        // --- Foco / Zen Mode ---
        Route::get('/meu-foco', [FocusController::class, 'index'])->name('focus.index');

        // --- Gamification / IA (Add this block) ---
        Route::get('/gamificacao/motivacao', [GamificationController::class, 'motivacao'])
            ->name('gamification.motivacao'); // <--- This fixes the error

        // Rotas de Ordenação (AJAX POST)
        Route::post('/reorder/fases', [OrderController::class, 'reorderFases'])->name('reorder.fases');
        Route::post('/reorder/tarefas', [OrderController::class, 'reorderTarefas'])->name('reorder.tarefas');
        Route::post('/reorder/global', [OrderController::class, 'reorderGlobal'])->name('reorder.global');

        // View de Árvore
        Route::get('/projeto/{id}/arvore', [ProjetoController::class, 'treeView'])->name('tree.view');

        // Comemoração
        Route::get('/tarefas/{id}/comemorar', [CelebrationController::class, 'show'])->name('celebration.show');

        // Cron Force
        Route::get('/forcar-envio-diario', function () {
            try {
                Artisan::call('treetask:daily-motivation');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Comando executado!',
                    'log' => Artisan::output()
                ]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        })->name('cron.force');

        // IA Commands
        Route::get('/ia-comando', [AiCommandController::class, 'index'])->name('ai.index');
        Route::post('/ia-comando/preview', [AiCommandController::class, 'preview'])->name('ai.preview');
        Route::post('/ia-comando/executar', [AiCommandController::class, 'execute'])->name('ai.execute');
    });
