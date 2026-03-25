<?php

use App\Modules\ANT\Http\Controllers\AdminAlunoController;
use App\Modules\ANT\Http\Controllers\AdminMateriaController;
use App\Modules\ANT\Http\Controllers\AdminProfessorController;
use App\Modules\ANT\Http\Controllers\AntAdminController;
use App\Modules\ANT\Http\Controllers\AuthController;
use App\Modules\ANT\Http\Controllers\CorrecaoController;
use App\Modules\ANT\Http\Controllers\MaterialController;
use App\Modules\ANT\Http\Controllers\PesoController;
use App\Modules\ANT\Http\Controllers\ProfessorController;
use App\Modules\ANT\Http\Controllers\ProvaController;
use App\Modules\ANT\Http\Controllers\TrabalhoController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;
use App\Modules\ANT\Http\Controllers\AntHomeController;

// Rotas de Autenticação do Módulo ANT
Route::prefix('ant')->name('ant.')->middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

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

        // Materiais de Aula (acessível por alunos matriculados e professores)
        Route::get('/materia/{idMateria}/materiais', [MaterialController::class, 'index'])->name('ant.materiais.index');

        Route::prefix('professor')->group(function () {
            // Dashboard
            Route::get('/', [ProfessorController::class, 'index'])->name('ant.professor.index');
            Route::get('/novo-trabalho', [ProfessorController::class, 'create'])->name('ant.professor.create');
            Route::post('/novo-trabalho', [ProfessorController::class, 'store'])->name('ant.professor.store');
            // Lista de Entregas de um Trabalho
            Route::get('/trabalho/{id}', [ProfessorController::class, 'trabalho'])->name('ant.professor.trabalho');
            // Editar Trabalho
            Route::get('/trabalho/{id}/editar', [ProfessorController::class, 'edit'])->name('ant.professor.trabalho.edit');
            Route::put('/trabalho/{id}/editar', [ProfessorController::class, 'update'])->name('ant.professor.trabalho.update');

            Route::get('/materia/{idMateria}/boletim', [ProfessorController::class, 'boletim'])->name('ant.professor.boletim');


            Route::get('/pesos', [PesoController::class, 'create'])->name('ant.pesos.create');
            Route::post('/pesos', [PesoController::class, 'store'])->name('ant.pesos.store');
            Route::delete('/pesos/{id}', [PesoController::class, 'destroy'])->name('ant.pesos.destroy');

            Route::post('/correcao/{idEntrega}/ia-sugestao', [CorrecaoController::class, 'iaSugestao'])->name('ant.correcao.ia_sugestao');

            // Upload de materiais de aula (professor)
            Route::get('/materia/{idMateria}/materiais/novo', [MaterialController::class, 'create'])->name('ant.materiais.create');
            Route::post('/materia/{idMateria}/materiais', [MaterialController::class, 'store'])->name('ant.materiais.store');
            Route::get('/material/{id}/editar', [MaterialController::class, 'edit'])->name('ant.materiais.edit');
            Route::post('/material/{id}', [MaterialController::class, 'update'])->name('ant.materiais.update');
            Route::delete('/material/{id}', [MaterialController::class, 'destroy'])->name('ant.materiais.destroy');
        });




        Route::prefix('admin')->group(function () {
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

    });
