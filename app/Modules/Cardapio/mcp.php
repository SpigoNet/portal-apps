<?php

use App\Modules\Cardapio\Http\Controllers\McpController;
use App\Modules\Cardapio\Http\Middleware\AutenticarMcp;
use Illuminate\Support\Facades\Route;

// MCP para agentes externos: stateless (grupo `api`, sem sessão/CSRF), autenticado por token.
Route::prefix('cardapio/mcp')
    ->name('cardapio.mcp.')
    ->middleware(['api', 'throttle:120,1'])
    ->group(function () {
        Route::post('/', [McpController::class, 'handle'])->middleware(AutenticarMcp::class)->name('handle');
        Route::get('/', [McpController::class, 'naoSuportado'])->name('get');

        // Credenciais na URL, para clientes que não deixam configurar headers.
        Route::post('/{userId}/{token}', [McpController::class, 'handle'])->middleware(AutenticarMcp::class)
            ->where(['userId' => '\d+', 'token' => '[A-Za-z0-9]+'])->name('handle.url');
        Route::get('/{userId}/{token}', [McpController::class, 'naoSuportado'])->name('get.url');
    });
