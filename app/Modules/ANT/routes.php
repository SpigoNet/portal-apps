<?php

use App\Modules\ANT\Http\Controllers\AdminAlunoController;
use App\Modules\ANT\Http\Controllers\AdminMateriaController;
use App\Modules\ANT\Http\Controllers\AdminProfessorController;
use App\Modules\ANT\Http\Controllers\AntAdminController;
use App\Modules\ANT\Http\Controllers\CorrecaoController;
use App\Modules\ANT\Http\Controllers\PesoController;
use App\Modules\ANT\Http\Controllers\ProfessorController;
use App\Modules\ANT\Http\Controllers\ProvaController;
use App\Modules\ANT\Http\Controllers\TrabalhoController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;
use App\Modules\ANT\Http\Controllers\AntHomeController;

// Grupo Principal com Prefixo 'ant' e Middleware de Autenticação
Route::prefix('ant')
    ->middleware(['web', 'auth'])
    ->middleware(RegistrarAcesso::class . ':ANT')
    ->group(function () {

    // Rota Inicial (Dashboard)
    Route::get('/', [AntHomeController::class, 'index'])->name('ant.home');

    // -- Rotas de Vínculo de RA (Para primeiro acesso do aluno) --
    Route::get('/vincular-ra', [AntHomeController::class, 'vincularRaView'])->name('ant.vincular_ra');
    Route::post('/vincular-ra', [AntHomeController::class, 'vincularRaStore'])->name('ant.vincular_ra.store');
// Rota para buscar alunos via AJAX (para grupos)
    Route::get('/api/alunos-busca', [TrabalhoController::class, 'buscarColegas'])->name('ant.api.alunos.busca');

    // Rota para ver detalhes do trabalho
    Route::get('/trabalho/{id}', [TrabalhoController::class, 'show'])->name('ant.trabalhos.show');
    // Rota para enviar o formulário
    Route::post('/trabalho/{id}', [TrabalhoController::class, 'store'])->name('ant.trabalhos.store');
    Route::get('/boletim/{idMateria}', [AntHomeController::class, 'boletim'])->name('ant.aluno.boletim');
    Route::get('/prova/{idTrabalho}/resultado', [ProvaController::class, 'resultado'])->name('ant.prova.resultado');

    Route::get('/correcao/{idEntrega}/{fileIndex?}', [CorrecaoController::class, 'edit'])->name('ant.correcao.edit');
    Route::post('/correcao/{idEntrega}', [CorrecaoController::class, 'update'])->name('ant.correcao.update');

    Route::prefix('professor')->group(function() {
        // Dashboard
        Route::get('/', [ProfessorController::class, 'index'])->name('ant.professor.index');
        Route::get('/novo-trabalho', [ProfessorController::class, 'create'])->name('ant.professor.create');
        Route::post('/novo-trabalho', [ProfessorController::class, 'store'])->name('ant.professor.store');
        // Lista de Entregas de um Trabalho
        Route::get('/trabalho/{id}', [ProfessorController::class, 'trabalho'])->name('ant.professor.trabalho');

        Route::get('/materia/{idMateria}/boletim', [ProfessorController::class, 'boletim'])->name('ant.professor.boletim');
    });

    Route::post('/correcao/{idEntrega}/ia-sugestao', [CorrecaoController::class, 'iaSugestao'])->name('ant.correcao.ia_sugestao');


    Route::prefix('admin')->group(function() {
        // Dashboard Admin (Já existia)
        Route::get('/', [AntAdminController::class, 'index'])->name('ant.admin.home');

        // CRUD de Matérias
        Route::resource('materias', AdminMateriaController::class)->names([
            'index' => 'ant.admin.materias.index',
            'create' => 'ant.admin.materias.create',
            'store' => 'ant.admin.materias.store',
            'edit' => 'ant.admin.materias.edit',
            'update' => 'ant.admin.materias.update',
            'destroy' => 'ant.admin.materias.destroy',
        ]);

        Route::resource('professores', AdminProfessorController::class)->names([
            'index' => 'ant.admin.professores.index',
            'create' => 'ant.admin.professores.create',
            'store' => 'ant.admin.professores.store',
            'destroy' => 'ant.admin.professores.destroy',
        ])->except(['show', 'edit', 'update']);

        // Gestão de Alunos (Listagem e Exclusão)
        Route::get('/alunos', [AdminAlunoController::class, 'index'])->name('ant.admin.alunos.index');
        Route::delete('/alunos/{id}', [AdminAlunoController::class, 'destroy'])->name('ant.admin.alunos.destroy');
        Route::get('/alunos/importar', [AdminAlunoController::class, 'importar'])->name('ant.admin.alunos.importar');
        Route::post('/alunos/importar', [AdminAlunoController::class, 'processarImportacao'])->name('ant.admin.alunos.processar');
    });

    Route::get('/pesos', [PesoController::class, 'create'])->name('ant.pesos.create');
    Route::post('/pesos', [PesoController::class, 'store'])->name('ant.pesos.store');
    Route::delete('/pesos/{id}', [PesoController::class, 'destroy'])->name('ant.pesos.destroy');
});
