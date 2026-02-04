<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Modules\Admin\Http\Controllers\AppManagerController;
use Illuminate\Support\Facades\Route;

// O grupo de rotas é protegido pelo nosso middleware, que verifica
// tanto a autenticação (se o usuário está logado) quanto a autorização (se é admin).
Route::middleware([EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Redireciona a rota base /admin para a lista de apps
    Route::get('/', fn() => redirect()->route('admin.apps.index'));

    // Modulo de gerenciamento de icones
    Route::get('/icon-generator', [App\Modules\Admin\Http\Controllers\IconGeneratorController::class, 'index'])->name('icon-generator');
    Route::post('/icon-generator', [App\Modules\Admin\Http\Controllers\IconGeneratorController::class, 'store'])->name('icon-generator.store');

    // Agrupa todas as rotas do CRUD (index, create, store, edit, update, destroy)
    Route::resource('apps', AppManagerController::class);

    // Gerenciamento de usuarios do app
    Route::resource('apps.users', App\Modules\Admin\Http\Controllers\AppUserManagerController::class)->only(['index', 'store', 'update', 'destroy'])->scoped([
        'user' => 'user' // Garante que o usuario pertença ao app? Nao necessariamente, user é global.
    ]);

    // Gerenciamento Geral de Usuários
    Route::resource('users', App\Modules\Admin\Http\Controllers\UserManagerController::class);

    // Gerenciamento de Pacotes
    Route::resource('packages', App\Modules\Admin\Http\Controllers\PackageManagerController::class);
});

