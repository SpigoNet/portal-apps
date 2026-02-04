<?php

use Illuminate\Support\Facades\Route;
use App\Modules\StreamingManager\Http\Controllers\StreamingController;
use App\Modules\StreamingManager\Http\Controllers\MemberController;
use App\Modules\StreamingManager\Http\Controllers\PaymentController;

Route::prefix('streaming-manager')->name('streaming-manager.')->group(function () {
    Route::get('/', [StreamingController::class, 'index'])->name('index');
    Route::get('/create', [StreamingController::class, 'create'])->name('create');
    Route::post('/', [StreamingController::class, 'store'])->name('store');
    Route::get('/{streaming}', [StreamingController::class, 'show'])->name('show');
    Route::get('/{streaming}/edit', [StreamingController::class, 'edit'])->name('edit');
    Route::put('/{streaming}', [StreamingController::class, 'update'])->name('update');
    Route::delete('/{streaming}', [StreamingController::class, 'destroy'])->name('destroy');

    Route::post('/{streaming}/members', [MemberController::class, 'store'])->name('members.store');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::post('/{streaming}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
});
