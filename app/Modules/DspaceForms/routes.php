<?php

use App\Modules\DspaceForms\Http\Controllers\DspaceEmailController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormFieldController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormMapController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormRowController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormsController;
use App\Modules\DspaceForms\Http\Controllers\DspaceValuePairsListController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('dspace-forms-editor')
    ->name('dspace-forms.')
    ->middleware(RegistrarAcesso::class.':DspaceForms')
    ->group(function () {
        Route::get('/', [DspaceFormsController::class, 'index'])->name('index');

        Route::get('/select-config/{configId}', [DspaceFormsController::class, 'selectConfig'])->name('select-config');
        Route::post('/clear-config', [DspaceFormsController::class, 'clearConfig'])->name('clear-config');

        Route::get('/configurations/create', [DspaceFormsController::class, 'create'])->name('configurations.create');
        Route::post('/configurations', [DspaceFormsController::class, 'store'])->name('configurations.store');
        Route::post('/configurations/{configuration}/duplicate', [DspaceFormsController::class, 'duplicate'])->name('configurations.duplicate');

        Route::get('/export', [DspaceFormsController::class, 'exportAllAsZip'])->name('export.zip');

        Route::prefix('value-pairs')->controller(DspaceValuePairsListController::class)->name('value-pairs.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{list}/edit', 'edit')->name('edit');
            Route::post('/{list}/store', 'store')->name('store');
            Route::put('/{list}/update/{pair}', 'update')->name('update');
            Route::delete('/{list}/destroy/{pair}', 'destroy')->name('destroy');
            Route::post('/{list}/move/{pair}', 'move')->name('move');
            Route::post('/{list}/sort-alpha', 'sortAlphabetical')->name('sort.alphabetical');
            Route::post('/', 'createList')->name('storeNewList');
            Route::delete('/{list}', 'destroyList')->name('destroyList');
        });

        Route::prefix('form-maps')->controller(DspaceFormMapController::class)->name('form-maps.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{map}', 'update')->name('update');
            Route::delete('/{map}', 'destroy')->name('destroy');
        });

        Route::prefix('forms')->controller(DspaceFormController::class)->name('forms.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{form}/edit', 'edit')->name('edit');
            Route::put('/{form}', 'update')->name('update');
            Route::delete('/{form}', 'destroy')->name('destroy');

            Route::prefix('{form}/rows')->name('rows.')->group(function () {
                Route::post('/', [DspaceFormRowController::class, 'store'])->name('store');
                Route::delete('/{row}', [DspaceFormRowController::class, 'destroy'])->name('destroy');
                Route::post('/{row}/move', [DspaceFormRowController::class, 'move'])->name('move');
            });

            Route::prefix('{form}/rows/{row}/fields')->name('rows.fields.')->group(function () {
                Route::post('/', [DspaceFormFieldController::class, 'store'])->name('store');
                Route::put('/{field}', [DspaceFormFieldController::class, 'update'])->name('update');
                Route::delete('/{field}', [DspaceFormFieldController::class, 'destroy'])->name('destroy');
                Route::post('/{field}/move', [DspaceFormFieldController::class, 'move'])->name('move');
            });

            Route::get('/field-data', [DspaceFormFieldController::class, 'getFieldData'])->name('field.data');
        });

        Route::prefix('emails')->controller(DspaceEmailController::class)->name('emails.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{template}/edit', 'edit')->name('edit');
            Route::put('/{template}', 'update')->name('update');
        });
    });
