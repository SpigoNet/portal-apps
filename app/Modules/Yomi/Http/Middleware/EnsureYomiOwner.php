<?php

namespace App\Modules\Yomi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureYomiOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((int) ($request->user()?->id ?? 0) !== (int) config('yomi.owner_user_id', 1)) {
            abort(404);
        }

        return $next($request);
    }
}
