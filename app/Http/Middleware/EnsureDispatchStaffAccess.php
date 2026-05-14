<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Khu vực đọc/ghi dành cho điều vận (admin, dispatcher, department_head…).
 * Tài khoản chỉ `internal_user` vẫn vào SPA (common-*) nhưng không được dùng route staff.
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
            abort(403, 'Yêu cầu tài khoản đã đăng nhập SPA điều vận.');
        }

        if ($user->hasRole('internal_user') && ! $user->hasAnyRole(['admin', 'dispatcher', 'department_head'])) {
            abort(403, 'Khu vực này dành cho điều vận (admin, dispatcher, trưởng đơn vị).');
        }

        return $next($request);
    }
}
