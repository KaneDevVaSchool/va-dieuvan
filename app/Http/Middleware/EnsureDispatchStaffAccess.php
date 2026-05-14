<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Khu vực web điều vận (admin, dispatcher, department_head) — không bao gồm tài khoản chỉ role driver.
 * Super admin luôn được (see User::canAccessDispatchWebApp).
 */
class EnsureDispatchStaffAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if (! $user->canAccessDispatchWebApp()) {
            abort(403, 'Yêu cầu tài khoản điều vận (superadmin, admin, dispatcher hoặc trưởng đơn vị).');
        }

        return $next($request);
    }
}
