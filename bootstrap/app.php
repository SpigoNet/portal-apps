<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // --- PONTO CRÍTICO ---
        // É aqui que informamos ao Laravel o que o apelido 'admin' significa.
        // Se esta seção estiver faltando ou incorreta, o arquivo do middleware nunca será chamado.
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        // --- FIM DO PONTO CRÍTICO ---
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
