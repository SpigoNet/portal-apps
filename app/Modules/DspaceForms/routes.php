<?php

use Illuminate\Support\Facades\Route;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormsController;

// Você pode criar um middleware específico se precisar de um controle de acesso mais granular
// Por enquanto, usaremos o middleware 'admin' que já existe
Route::middleware(['web', 'admin'])
    ->prefix('dspace-forms-editor')
    ->name('dspace-forms.')
    ->group(function () {
        Route::get('/', [DspaceFormsController::class, 'index'])->name('index');
        Route::get('/export-all-zip', [DspaceFormsController::class, 'exportAllAsZip'])->name('export.zip');

        // Aqui entrariam as rotas para os CRUDs completos
        // Ex: Route::resource('maps', FormMapController::class);
        // Ex: Route::resource('lists', ValuePairListController::class);
        // Ex: Route::resource('lists.pairs', ValuePairController::class);
    });
