<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    /**
     * Usage:
     * - permission:request.create
     * - permission:any,request.create,request.approve
     * - permission:all,trip.assign,trip.view_all
     */
    public function handle(Request $request, Closure $next, string $permission, string ...$more): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $parts = [$permission, ...$more];
        $mode = 'any';
        if (count($parts) > 1 && in_array(strtolower((string) $parts[0]), ['any', 'all'], true)) {
            $mode = strtolower((string) array_shift($parts));
        }

        $perms = collect($parts)
            ->flatMap(fn ($p) => explode(',', (string) $p))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values()
            ->all();

        if (!$perms) {
            abort(403);
        }

        $ok = $mode === 'all'
            ? collect($perms)->every(fn ($p) => $user->hasPermission($p))
            : collect($perms)->some(fn ($p) => $user->hasPermission($p));

        if (!$ok) {
            abort(403);
        }

        return $next($request);
    }
}

