<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\Yomi\Http\Controllers\YomiController;
use App\Modules\Yomi\Http\Controllers\YomiSettingsController;
use App\Modules\Yomi\Http\Middleware\EnsureYomiOwner;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Services\YomiSettingsService;
use Illuminate\Support\Facades\Route;

Route::prefix('yomi')
    ->name('yomi.')
    ->middleware(['web', 'auth', RegistrarAcesso::class.':Yomi'])
    ->group(function () {

        Route::get('/', [YomiController::class, 'index'])->name('index');
        Route::get('/biblioteca', [YomiController::class, 'library'])->name('library');
        Route::get('/estatisticas', [YomiController::class, 'stats'])->name('stats');
        Route::get('/descobrir', [YomiController::class, 'discover'])->name('discover');
        Route::get('/descobrir/{provider}/{externalId}', [YomiController::class, 'showExternal'])->name('discover.external');
        Route::post('/descobrir', [YomiController::class, 'registerFromDiscover'])->name('discover.register');
        Route::get('/mangas/{manga}', [YomiController::class, 'show'])->name('mangas.show');
        Route::post('/mangas/{manga}/proximo-capitulo', [YomiController::class, 'markNext'])->name('mangas.mark-next');
        Route::post('/mangas/{manga}/sincronizar-capitulos', [YomiController::class, 'syncChapters'])->name('mangas.sync-chapters');
        Route::post('/mangas/{manga}/capitulos/{capitulo}/toggle', [YomiController::class, 'toggleChapter'])->name('mangas.chapters.toggle');
        Route::post('/mangas/{manga}/remover', [YomiController::class, 'removeFromLibrary'])->name('mangas.remove');

        // Endpoint de saúde/infra: camada de consumo será definida na etapa de UI.
        Route::get('/status', function () {
            return response()->json([
                'module' => 'yomi',
                'status' => 'up',
                'mangas' => Manga::count(),
                'provedores' => app(YomiSettingsService::class)->orderedNames(),
            ]);
        })->name('status');

        Route::group(['middleware' => EnsureYomiOwner::class], function () {
            Route::get('/configuracoes', [YomiSettingsController::class, 'edit'])->name('settings');
            Route::post('/configuracoes', [YomiSettingsController::class, 'update'])->name('settings.update');
            Route::get('/configuracoes/provedores/{provider}/saude', [YomiSettingsController::class, 'health'])
                ->name('settings.providers.health')
                ->where('provider', '[a-z]+');
        });
    });
