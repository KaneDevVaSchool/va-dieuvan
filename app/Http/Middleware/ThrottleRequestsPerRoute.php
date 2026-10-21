<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * `throttle:30,1` mặc định của Laravel dùng key = sha1(user id) cho MỌI route,
 * nên các route chung một bộ đếm (route giới hạn 30 bị chặn sau 30 request bất kỳ)
 * và group lồng nhau bị đếm nhiều lần. Ở đây mỗi route + mức giới hạn có bộ đếm riêng.
 * Named limiter (`throttle:driver-workflow-read`, …) giữ nguyên hành vi.
 */
class ThrottleRequestsPerRoute extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (is_string($maxAttempts) && func_num_args() === 3 && $this->limiter->limiter($maxAttempts) !== null) {
            return parent::handle($request, $next, $maxAttempts);
        }

        $route = $request->route();
        $routeKey = $route ? implode('|', $route->methods()).':'.$route->uri() : $request->path();

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix.$routeKey.'|'.$maxAttempts.'/'.$decayMinutes.'|');
    }
}
