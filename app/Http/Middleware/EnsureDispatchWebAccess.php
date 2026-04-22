<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDispatchWebAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->canAccessDispatchWebApp() && ! $user->canAccessDriverWebApp()) {
            abort(403, 'Tài khoản không có quyền truy cập. Cần vai trò superadmin, admin, dispatcher hoặc tài xế (driver).');
        }

        return $next($request);
    }
}
