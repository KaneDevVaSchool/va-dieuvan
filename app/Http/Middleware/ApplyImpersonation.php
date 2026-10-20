<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use App\Support\Auth\ImpersonationToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplyImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = ImpersonationToken::current($request);
        if (! $token) {
            return $next($request);
        }

        $target = $request->user();
        $actor = User::find(ImpersonationToken::actorId($token));
        $roleName = ImpersonationToken::roleName($token);
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
        $request->attributes->set('impersonation', [
            'active' => true,
            'impersonator' => $actor ? ImpersonationToken::summary($actor) : null,
            'role' => ['name' => $roleName, 'label' => $role?->display_name ?? $roleName],
            'expires_at' => $token->expires_at?->toIso8601String(),
        ]);

        // Leaving remains possible if a role/account was changed during the session.
        if ($request->is('api/impersonate/leave')) {
            return $next($request);
        }

        abort_unless($actor && $actor->is_active && $actor->isSuperAdmin(), 401, 'Phiên giả lập không còn hợp lệ.');
        abort_unless($target->is_active && ! $target->isSuperAdmin() && $role && $target->hasRole($roleName), 401, 'Vai trò giả lập đã thay đổi.');

        $effectiveUser = clone $target;
        $effectiveUser->simulateRole($role);
        $resolver = $request->getUserResolver();
        $guard = Auth::guard();
        $guard->setUser($effectiveUser);
        $request->setUserResolver(fn ($guard = null) => $effectiveUser);

        try {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        } finally {
            $guard->setUser($target);
            $request->setUserResolver($resolver);
        }
    }
}
