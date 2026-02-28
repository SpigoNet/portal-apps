<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetModuleContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $firstSegment = $request->segment(1);

        if ($this->isModuleSegment($firstSegment)) {
            Session::put('module_origin', $firstSegment);
            Session::put('module_home_url', url('/' . $firstSegment));

            if ($request->isMethod('GET')) {
                Session::put('module_last_url', $request->fullUrl());
            }
        }

        return $next($request);
    }

    private function isModuleSegment(?string $segment): bool
    {
        if (!is_string($segment) || trim($segment) === '') {
            return false;
        }

        return !in_array($segment, [
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'confirm-password',
            'profile',
            'storage',
            'build',
            'up',
        ], true);
    }
}
