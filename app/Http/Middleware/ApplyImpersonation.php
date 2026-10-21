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
        $portal = ImpersonationToken::isPortal($token);
        $roleName = $portal ? null : ImpersonationToken::roleName($token);
        $role = $roleName ? Role::where('name', $roleName)->where('guard_name', 'web')->first() : null;
        $request->attributes->set('impersonation', [
            'active' => true,
            'impersonator' => $actor ? ImpersonationToken::summary($actor) : null,
            'portal' => $portal,
            'role' => $portal
                ? ['name' => null, 'label' => ImpersonationToken::PORTAL_LABEL]
                : ['name' => $roleName, 'label' => $role?->display_name ?? $roleName],
            'expires_at' => $token->expires_at?->toIso8601String(),
        ]);

        // Leaving remains possible if a role/account was changed during the session.
        if ($request->is('api/impersonate/leave')) {
            return $next($request);
        }

        abort_unless($actor && $actor->canImpersonate(), 401, 'Phiên giả lập không còn hợp lệ.');
        abort_unless($target->is_active && ! $target->isSuperAdmin(), 401, 'Phiên giả lập không còn hợp lệ.');
        abort_unless($portal || ($role && $target->hasRole($roleName)), 401, 'Vai trò giả lập đã thay đổi.');

        $effectiveUser = clone $target;
        // Portal: không vai trò nào, chỉ còn quyền gán trực tiếp — giống người yêu cầu chưa được phân vai trò.
        $effectiveUser->simulateRole($portal ? null : $role);
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
