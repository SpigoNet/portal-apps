<?php

use App\Modules\MundosDeMim\Http\Controllers\DashboardController;
use App\Modules\MundosDeMim\Http\Controllers\EstilosController;
use App\Modules\MundosDeMim\Http\Controllers\GaleriaController;
use App\Modules\MundosDeMim\Http\Controllers\PerfilBiometricoController;
use App\Modules\MundosDeMim\Http\Controllers\PessoasRelacionadasController; // <--- Importar
use App\Modules\MundosDeMim\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::prefix('mundos-de-mim')
    ->name('mundos-de-mim.')
    ->group(function () {

        // Rota Principal Pública (Landing Page)
        Route::get('/', [DashboardController::class, 'landing'])->name('landing');

        // Rotas Protegidas que Exigem Login
        Route::middleware(['auth'])->group(function () {

            // Dashboard Interno
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('index');

            // Rotas de Biometria
            Route::get('/meu-perfil', [PerfilBiometricoController::class, 'index'])->name('perfil.index');
            Route::post('/meu-perfil', [PerfilBiometricoController::class, 'update'])->name('perfil.update');
            Route::post('/meu-perfil/analisar', [PerfilBiometricoController::class, 'analyze'])->name('perfil.analyze');

            // Rotas de Pessoas
            Route::get('/pessoas', [PessoasRelacionadasController::class, 'index'])->name('pessoas.index');
            Route::get('/pessoas/adicionar', [PessoasRelacionadasController::class, 'create'])->name('pessoas.create');
            Route::post('/pessoas', [PessoasRelacionadasController::class, 'store'])->name('pessoas.store');
            Route::post('/pessoas/{id}/toggle', [PessoasRelacionadasController::class, 'toggleActive'])->name('pessoas.toggle');

            // Rota da Galeria
            Route::get('/galeria', [GaleriaController::class, 'index'])->name('galeria.index');

            // Rota de Estilos
            Route::get('/estilos', [EstilosController::class, 'index'])->name('estilos.index');
            Route::post('/estilos/toggle', [EstilosController::class, 'toggle'])->name('estilos.toggle');

            Route::get('/playground', [PlaygroundController::class, 'index'])->name('playground.index');
            Route::post('/playground', [PlaygroundController::class, 'generate'])->name('playground.generate');
            Route::post('/playground/refinar', [PlaygroundController::class, 'refine'])->name('playground.refine');
        });

    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('mundos-de-mim/admin')
    ->name('mundos-de-mim.admin.')
    ->group(function () {

        // CRUD de Temas
        Route::get('/temas', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'index'])->name('themes.index');
        Route::get('/temas/novo', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'create'])->name('themes.create');
        Route::post('/temas', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'store'])->name('themes.store');
        Route::get('/temas/{id}/editar', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'edit'])->name('themes.edit');
        Route::put('/temas/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'update'])->name('themes.update');
        Route::delete('/temas/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'destroy'])->name('themes.destroy');
        Route::delete('/temas/exemplo/{example_id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminThemeController::class, 'destroyExample'])
            ->name('themes.destroyExample');

        Route::prefix('prompts')->name('prompts.')->group(function () {
            // Tela de criação vinculada a um tema
            Route::get('/novo/{theme_id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminPromptController::class, 'create'])->name('create');
            Route::post('/', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminPromptController::class, 'store'])->name('store');

            // O "Atalho" de gerenciamento que você pediu: Edição
            Route::get('/{id}/editar', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminPromptController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminPromptController::class, 'update'])->name('update');

            Route::delete('/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminPromptController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('importador')->name('importador.')->group(function () {
            Route::get('/', [\App\Modules\MundosDeMim\Http\Controllers\Admin\PromptImporterController::class, 'index'])->name('index');
            Route::post('/analisar', [\App\Modules\MundosDeMim\Http\Controllers\Admin\PromptImporterController::class, 'analyze'])->name('analyze');
            Route::post('/confirmar', [\App\Modules\MundosDeMim\Http\Controllers\Admin\PromptImporterController::class, 'store'])->name('store');
        });

        // Gerenciador de Galeria Pública
        Route::prefix('galeria-publica')->name('gallery.')->group(function () {
            Route::get('/', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminGalleryController::class, 'index'])->name('index');
            Route::post('/copy', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminGalleryController::class, 'copyToPublic'])->name('copy');
            Route::delete('/delete', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminGalleryController::class, 'deleteFromPublic'])->name('delete');
        });

        // Gerenciador de Provedores de IA
        Route::prefix('provedores-ia')->name('ai-providers.')->group(function () {
            Route::get('/', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'index'])->name('index');
            Route::get('/novo', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'create'])->name('create');
            Route::post('/', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'store'])->name('store');
            Route::get('/{id}/editar', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/padrao', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'setDefault'])->name('set-default');
            Route::post('/seed', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'seed'])->name('seed');
            Route::get('/usuarios', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'userSettings'])->name('user-settings');
            Route::post('/usuarios', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'updateUserSettings'])->name('update-user-settings');
            Route::post('/global', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'updateGlobalDefault'])->name('update-global-default');
            Route::post('/sync-pollination', [\App\Modules\MundosDeMim\Http\Controllers\Admin\AdminAiProviderController::class, 'syncPollination'])->name('sync-pollination');
        });
    });
