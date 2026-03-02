<?php

use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::get('/manifest/{id}/manifest.json', [ManifestController::class, 'show'])->name('pwa.manifest');

// Rota para rodar migration forçadamente
Route::get('/run-migrations', function () {
    \Artisan::call('migrate', ['--force' => true]);

    return 'Migrations executed successfully.';
})->middleware('auth')->name('run.migrations');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Route::get('/microsoft/redirect', [MicrosoftController::class, 'redirect'])->name('microsoft.redirect');
// Route::get('/microsoft/callback', [MicrosoftController::class, 'callback'])->name('microsoft.callback');

require __DIR__.'/auth.php';

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('ai-providers', AiProviderController::class);
    Route::post('ai-providers/{id}/sync', [AiProviderController::class, 'sync'])->name('ai-providers.sync');
    Route::resource('ai-models', AiModelController::class);
});
