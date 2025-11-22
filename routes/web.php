<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\WelcomeController; // Adicionado
use App\Http\Controllers\DashboardController;


Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Rota principal unificada
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Redireciona dashboard antigo para a home
Route::get('/dashboard', function () {
    return redirect()->route('welcome');
})->name('dashboard');


Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
