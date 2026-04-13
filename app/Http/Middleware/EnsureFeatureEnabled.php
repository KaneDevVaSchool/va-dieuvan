<?php

namespace App\Http\Middleware;

use App\Services\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (! app(FeatureToggleService::class)->isEnabled($key)) {
            abort(403, 'Tính năng đang tắt.');
        }

        return $next($request);
    }
}
