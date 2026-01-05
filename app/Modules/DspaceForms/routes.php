<?php

use App\Modules\DspaceForms\Http\Controllers\DspaceEmailController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormFieldController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormMapController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormRowController;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;
use App\Modules\DspaceForms\Http\Controllers\DspaceValuePairsListController;
use App\Modules\DspaceForms\Http\Controllers\DspaceFormsController;

// Você pode criar um middleware específico se precisar de um controle de acesso mais granular
// Por enquanto, usaremos o middleware 'admin' que já existe
Route::middleware(['web'])
    ->prefix('dspace-forms-editor')
    ->name('dspace-forms.')
    ->middleware(RegistrarAcesso::class . ':DspaceForms')
    ->group(function () {
        // Rota principal: Lida com a seleção da configuração (se config_id não estiver presente)
        // OU exibe o dashboard filtrado (se config_id estiver presente).
        Route::get('/', [DspaceFormsController::class, 'index'])->name('index');

        // Rotas de Gerenciamento de Configurações
        Route::get('/configurations/create', [DspaceFormsController::class, 'create'])->name('configurations.create');
        Route::post('/configurations', [DspaceFormsController::class, 'store'])->name('configurations.store');
        Route::post('/configurations/{configuration}/duplicate', [DspaceFormsController::class, 'duplicate'])->name('configurations.duplicate');

        // Rota de Exportação (Agora exige o ID da configuração)
        Route::get('/export/{configId}', [DspaceFormsController::class, 'exportAllAsZip'])->name('export.zip');

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

        Route::prefix('forms')->controller(DspaceFormController::class)->name('forms.')->group(function () {
            // ... (Rotas CRUD de DspaceForm)
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{form}/edit', 'edit')->name('edit');
            Route::put('/{form}', 'update')->name('update');
            Route::delete('/{form}', 'destroy')->name('destroy');

            // Sub-Rotas para Linhas (Rows)
            Route::prefix('{form}/rows')->name('rows.')->group(function () {
                Route::post('/', [DspaceFormRowController::class, 'store'])->name('store');
                Route::delete('/{row}', [DspaceFormRowController::class, 'destroy'])->name('destroy');
                Route::post('/{row}/move', [DspaceFormRowController::class, 'move'])->name('move'); // NOVO: Mover linha
            });

            // Sub-Rotas para Campos (Fields)
            Route::prefix('{form}/rows/{row}/fields')->name('rows.fields.')->group(function () {
                Route::post('/', [DspaceFormFieldController::class, 'store'])->name('store');
                Route::put('/{field}', [DspaceFormFieldController::class, 'update'])->name('update');
                Route::delete('/{field}', [DspaceFormFieldController::class, 'destroy'])->name('destroy');
                Route::post('/{field}/move', [DspaceFormFieldController::class, 'move'])->name('move'); // NOVO: Mover campo
            });

            // Rota para obter dados de configuração para o formulário de campo (manter para o modal)
            Route::get('/field-data', [DspaceFormFieldController::class, 'getFieldData'])->name('field.data');
        });

        Route::prefix('emails')->controller(DspaceEmailController::class)->name('emails.')->group(function () {
            Route::get('/', 'index')->name('index'); // Lista todas as templates
            Route::get('/{template}/edit', 'edit')->name('edit'); // Edita a template
            Route::put('/{template}', 'update')->name('update'); // Salva a template
        });

    });
