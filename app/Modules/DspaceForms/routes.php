<?php

use App\Modules\DspaceForms\Http\Controllers\DspaceFormMapController;
use Illuminate\Support\Facades\Route;
use App\Modules\DspaceForms\Http\Controllers\DspaceValuePairsListController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormsController;

// Você pode criar um middleware específico se precisar de um controle de acesso mais granular
// Por enquanto, usaremos o middleware 'admin' que já existe
Route::middleware(['web', 'admin'])
    ->prefix('dspace-forms-editor')
    ->name('dspace-forms.')
    ->group(function () {
        Route::get('/', [DspaceFormsController::class, 'index'])->name('index');
        Route::get('/export-all-zip', [DspaceFormsController::class, 'exportAllAsZip'])->name('export.zip');

        // Rotas para Listas de Valores (Value Pairs)
        Route::prefix('value-pairs')->controller(DspaceValuePairsListController::class)->name('value-pairs.')->group(function () {
            Route::get('/', 'index')->name('index'); // Lista todas as listas
            Route::get('/{list}/edit', 'edit')->name('edit'); // Edita itens de uma lista
            Route::post('/{list}/store', 'store')->name('store'); // Adiciona item
            Route::put('/{list}/update/{pair}', 'update')->name('update'); // Atualiza item (usado com formulário inline)
            Route::delete('/{list}/destroy/{pair}', 'destroy')->name('destroy'); // Remove item
            Route::post('/{list}/move/{pair}', 'move')->name('move'); // Mover item (up/down)

            Route::post('/{list}/sort-alpha', 'sortAlphabetical')->name('sort.alphabetical');
            Route::post('/', 'createList')->name('storeNewList'); // Cria nova lista
            Route::delete('/{list}', 'destroyList')->name('destroyList'); // Remove lista
        });

        // Rotas para Vínculos (Form Maps)
        Route::prefix('form-maps')->controller(DspaceFormMapController::class)->name('form-maps.')->group(function () {
            Route::get('/', 'index')->name('index'); // Lista todos os vínculos
            Route::post('/', 'store')->name('store'); // Salva um novo vínculo
            Route::put('/{map}', 'update')->name('update'); // Atualiza um vínculo
            Route::delete('/{map}', 'destroy')->name('destroy'); // Exclui um vínculo
        });

    });
