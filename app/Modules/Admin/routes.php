<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Http\Controllers\AppManagerController;

// O grupo de rotas exige que o usuário esteja logado (auth) E seja admin (admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Redireciona a rota base /admin para a lista de apps
    Route::get('/', fn() => redirect()->route('admin.apps.index'));

    // Agrupa todas as rotas do CRUD (index, create, store, edit, update, destroy)
    Route::resource('apps', AppManagerController::class);
});
