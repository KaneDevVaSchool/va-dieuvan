<?php

namespace App\Http\Middleware;

use App\Services\Auditing\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        // Only log mutating requests to reduce noise.
        if (! in_array(strtoupper((string) $request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        // Avoid logging audit-log reads to prevent recursion/noise.
        $path = '/'.ltrim((string) $request->path(), '/');
        if (str_starts_with($path, '/api/audit-logs')) {
            return $response;
        }

        $user = $request->user();
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'api.request',
            auditable: null,
            before: null,
            after: null,
            metadata: [
                'method' => strtoupper((string) $request->method()),
                'path' => $path,
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return $response;
    }
}
