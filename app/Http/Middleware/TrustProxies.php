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
        $request->setTrustedProxies($this->proxies, $this->getHeaders());

        if ($this->shouldForceSecureUrls($request)) {
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

    protected function shouldForceSecureUrls(Request $request): bool
    {
        return $request->header('x-forwarded-proto') === 'https'
               || $request->header('x-forwarded-ssl') === 'on';
    }
}
