<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverWebAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if (! $user->canAccessDriverWebApp()) {
            abort(403, 'Yêu cầu tài khoản tài xế (driver).');
        }

        return $next($request);
    }
}
