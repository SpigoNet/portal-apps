<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Modules\Admin\Http\Controllers\AppManagerController;
use Illuminate\Support\Facades\Route;

// O grupo de rotas é protegido pelo nosso middleware, que verifica
// tanto a autenticação (se o usuário está logado) quanto a autorização (se é admin).
Route::middleware([EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Redireciona a rota base /admin para a lista de apps
    Route::get('/', fn () => redirect()->route('admin.apps.index'));

    // Agrupa todas as rotas do CRUD (index, create, store, edit, update, destroy)
    Route::resource('apps', AppManagerController::class);
});

