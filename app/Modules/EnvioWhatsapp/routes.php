<?php

use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;
use App\Modules\EnvioWhatsapp\Http\Controllers\WhatsappController;

Route::middleware(['web'])
    ->prefix('ferramentas/whatsapp')
    ->name('envio-whatsapp.')
    ->middleware(RegistrarAcesso::class . ':EnvioWhatsapp')
    ->group(function () {

        // Passo 1: Upload
        Route::get('/', [WhatsappController::class, 'step1'])->name('index');
        Route::post('/upload', [WhatsappController::class, 'step2'])->name('upload');

        // Passo 3: Processamento Final (A configuração vem do Passo 2)
        Route::post('/processar', [WhatsappController::class, 'step3'])->name('process');
    });
