<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\TreeTask\Http\Controllers\AiCommandController;
use App\Modules\TreeTask\Http\Controllers\AnexoController;
use App\Modules\TreeTask\Http\Controllers\ApiController;
use App\Modules\TreeTask\Http\Controllers\CelebrationController;
use App\Modules\TreeTask\Http\Controllers\FaseController;
use App\Modules\TreeTask\Http\Controllers\FocusController;
use App\Modules\TreeTask\Http\Controllers\GamificationController;
use App\Modules\TreeTask\Http\Controllers\GoodMorningController;
use App\Modules\TreeTask\Http\Controllers\OrderController;
use App\Modules\TreeTask\Http\Controllers\ProjetoController;
use App\Modules\TreeTask\Http\Controllers\TarefaController;
use App\Modules\TreeTask\Http\Middleware\TokenAuth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// ==================== API V1 (Token Auth) ====================
Route::prefix('treetask/api/v1')
    ->name('treetask.api.')
    ->group(function () {
        // Health Check (público)
        Route::get('/health', [ApiController::class, 'health'])->name('health');

        // Rotas protegidas por TokenAuth
        Route::middleware(TokenAuth::class)->group(function () {
            // Projetos
            Route::get('/projetos', [ApiController::class, 'projetosIndex'])->name('projetos.index');
            Route::get('/projetos/{id}', [ApiController::class, 'projetosShow'])->name('projetos.show');
            Route::post('/projetos', [ApiController::class, 'projetosStore'])->name('projetos.store');
            Route::put('/projetos/{id}', [ApiController::class, 'projetosUpdate'])->name('projetos.update');
            Route::delete('/projetos/{id}', [ApiController::class, 'projetosDestroy'])->name('projetos.destroy');

            // Fases
            Route::get('/projetos/{id_projeto}/fases', [ApiController::class, 'fasesIndex'])->name('fases.index');
            Route::get('/fases/{id}', [ApiController::class, 'fasesShow'])->name('fases.show');
            Route::post('/fases', [ApiController::class, 'fasesStore'])->name('fases.store');
            Route::put('/fases/{id}', [ApiController::class, 'fasesUpdate'])->name('fases.update');
            Route::delete('/fases/{id}', [ApiController::class, 'fasesDestroy'])->name('fases.destroy');

            // Tarefas - CRUD Básico
            Route::get('/fases/{id_fase}/tarefas', [ApiController::class, 'tarefasIndex'])->name('tarefas.index');
            Route::get('/tarefas/{id}', [ApiController::class, 'tarefasShow'])->name('tarefas.show');
            Route::post('/tarefas', [ApiController::class, 'tarefasStore'])->name('tarefas.store');
            Route::put('/tarefas/{id}', [ApiController::class, 'tarefasUpdate'])->name('tarefas.update');
            Route::patch('/tarefas/{id}/status', [ApiController::class, 'tarefasUpdateStatus'])->name('tarefas.updateStatus');
            Route::delete('/tarefas/{id}', [ApiController::class, 'tarefasDestroy'])->name('tarefas.destroy');

            // Tarefas - Endpoints Especiais para Integração
            Route::get('/tarefas', [ApiController::class, 'tarefasList'])->name('tarefas.list');
            Route::get('/tarefas/relatorio/manha', [ApiController::class, 'tarefasRelatorio'])->name('tarefas.relatorio');
            Route::get('/tarefas/paradas', [ApiController::class, 'tarefasParadas'])->name('tarefas.paradas');
            Route::get('/tarefas/{id}/completa', [ApiController::class, 'tarefasCompleta'])->name('tarefas.completa');

            // Anexos
            Route::get('/tarefas/{id_tarefa}/anexos', [ApiController::class, 'anexosIndex'])->name('anexos.index');
            Route::get('/anexos/{id}', [ApiController::class, 'anexosShow'])->name('anexos.show');
            Route::post('/tarefas/{id_tarefa}/anexos', [ApiController::class, 'anexosStore'])->name('anexos.store');
            Route::delete('/tarefas/{id_tarefa}/anexos/{id_anexo}', [ApiController::class, 'anexosDestroy'])->name('anexos.destroy');
            Route::get('/anexos/{id_anexo}/download', [ApiController::class, 'anexosDownload'])->name('anexos.download');
        });
    });

Route::prefix('treetask')
    ->name('treetask.')
    ->middleware(['web', 'auth'])
    ->middleware(RegistrarAcesso::class.':TreeTask')
    ->group(function () {

        // --- Projetos ---
        Route::get('/', [ProjetoController::class, 'index'])->name('index');
        Route::get('/criar', [ProjetoController::class, 'create'])->name('create');
        Route::post('/', [ProjetoController::class, 'store'])->name('store');
        Route::get('/bom-dia', [GoodMorningController::class, 'index'])->name('good_morning');
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
                    'log' => Artisan::output(),
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
