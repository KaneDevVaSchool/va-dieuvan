<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * No-op replacement for throttle middleware (avoids HTTP 429 / "Too Many Attempts").
 */
class SkipThrottle
{
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        return $next($request);
    }
}
