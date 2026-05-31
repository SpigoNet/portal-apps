<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\URL;

class TrustProxies
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->setTrustedProxies($this->proxies, $this->getHeaders());
        
        // Force HTTPS URL generation if behind a proxy that terminates SSL
        if ($this->shouldForceSecureUrls($request)) {
            URL::forceScheme('https');
        }

        return $next($request);
    }

    /**
     * Get the headers that should be used to detect proxies.
     *
     * @return int
     */
    protected function getHeaders(): int
    {
        // Use the combined headers equivalent to HEADER_X_FORWARDED_ALL
        return Request::HEADER_X_FORWARDED_FOR 
               | Request::HEADER_X_FORWARDED_HOST 
               | Request::HEADER_X_FORWARDED_PORT 
               | Request::HEADER_X_FORWARDED_PROTO;
    }

    /**
     * Determine if we should force secure URLs for generation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldForceSecureUrls(Request $request): bool
    {
        return $request->header('x-forwarded-proto') === 'https' ||
               $request->header('x-forwarded-ssl') === 'on';
    }
}

        return $next($request);
    }

    /**
     * Get the headers that should be used to detect proxies.
     */
    protected function getHeaders(): int
    {
        return Request::HEADER_X_FORWARDED_ALL ??
               (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_ALL') ?
                Illuminate\Http\Request::HEADER_X_FORWARDED_ALL : 30);
    }

    /**
     * Determine if we should force secure URLs for generation.
     */
    protected function shouldForceSecureUrls(Request $request): bool
    {
        return $request->header('x-forwarded-proto') === 'https' ||
               $request->header('x-forwarded-ssl') === 'on';
    }
}
