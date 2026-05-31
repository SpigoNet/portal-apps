<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies
{
    protected $proxies = '*';

    public function handle(Request $request, Closure $next): Response
    {
        $proxies = $this->proxies === '*'
            ? [$request->server->get('REMOTE_ADDR')]
            : (array) $this->proxies;

        $request->setTrustedProxies($proxies, $this->getHeaders());

        if ($request->header('x-forwarded-proto') === 'https'
            || $request->header('x-forwarded-ssl') === 'on') {
            URL::forceScheme('https');
        }

        return $next($request);
    }

    protected function getHeaders(): int
    {
        return Request::HEADER_X_FORWARDED_FOR
               | Request::HEADER_X_FORWARDED_HOST
               | Request::HEADER_X_FORWARDED_PORT
               | Request::HEADER_X_FORWARDED_PROTO;
    }
}
