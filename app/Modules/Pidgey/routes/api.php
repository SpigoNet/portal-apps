<?php

use App\Modules\Pidgey\Http\Controllers\MensagemController;
use App\Modules\Pidgey\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [MensagemController::class, 'health'])->name('health');

Route::post('/mensagens', [MensagemController::class, 'enviar'])->name('mensagens.enviar');

Route::post('/resumo-financeiro', [MensagemController::class, 'resumoFinanceiro'])->name('resumo-financeiro.enviar');

Route::post('/webhook/telegram/{persona}', [WebhookController::class, 'receber'])->name('webhook.telegram');
