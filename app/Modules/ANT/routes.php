<?php

use App\Modules\ANT\Http\Controllers\AntAdminController;
use App\Modules\ANT\Http\Controllers\CorrecaoController;
use App\Modules\ANT\Http\Controllers\ProfessorController;
use App\Modules\ANT\Http\Controllers\ProvaController;
use App\Modules\ANT\Http\Controllers\TrabalhoController;
use Illuminate\Support\Facades\Route;
use App\Modules\ANT\Http\Controllers\AntHomeController;

// Grupo Principal com Prefixo 'ant' e Middleware de Autenticação
Route::prefix('ant')->middleware(['web', 'auth'])->group(function () {

    // Rota Inicial (Dashboard)
    Route::get('/', [AntHomeController::class, 'index'])->name('ant.home');

    // -- Rotas de Vínculo de RA (Para primeiro acesso do aluno) --
    Route::get('/vincular-ra', [AntHomeController::class, 'vincularRaView'])->name('ant.vincular_ra');
    Route::post('/vincular-ra', [AntHomeController::class, 'vincularRaStore'])->name('ant.vincular_ra.store');


    // Rota para ver detalhes do trabalho
    Route::get('/trabalho/{id}', [TrabalhoController::class, 'show'])->name('ant.trabalhos.show');
    // Rota para enviar o formulário
    Route::post('/trabalho/{id}', [TrabalhoController::class, 'store'])->name('ant.trabalhos.store');

    Route::get('/prova/{idTrabalho}/resultado', [ProvaController::class, 'resultado'])->name('ant.prova.resultado');

    Route::get('/correcao/{idEntrega}/{fileIndex?}', [CorrecaoController::class, 'edit'])->name('ant.correcao.edit');
    Route::post('/correcao/{idEntrega}', [CorrecaoController::class, 'update'])->name('ant.correcao.update');

    Route::prefix('professor')->group(function() {
        // Dashboard
        Route::get('/', [ProfessorController::class, 'index'])->name('ant.professor.index');

        // Lista de Entregas de um Trabalho
        Route::get('/trabalho/{id}', [ProfessorController::class, 'trabalho'])->name('ant.professor.trabalho');
    });

    Route::post('/correcao/{idEntrega}/ia-sugestao', [CorrecaoController::class, 'iaSugestao'])->name('ant.correcao.ia_sugestao');

    Route::get('/admin', [AntAdminController::class, 'index'])->name('ant.admin.home');
});
