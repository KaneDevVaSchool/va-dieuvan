<?php

namespace App\Http\Middleware;

use App\Models\IdempotentRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    /**
     * When header Idempotency-Key (or X-Idempotency-Key) is present and length >= 8,
     * replay identical 2xx JSON responses for the same user + named route scope.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
        if (! $raw || ! is_string($raw) || strlen(trim($raw)) < 8) {
            return $next($request);
        }

        $raw = trim($raw);
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route?->getName();
        if (! $name) {
            return $next($request);
        }

        $params = Collection::make($route?->parameters() ?? [])
            ->map(function ($v) {
                if (is_object($v) && method_exists($v, 'getRouteKey')) {
                    return $v->getRouteKey();
                }

                return $v;
            })
            ->sortKeys();

        $scope = $name.'|'.$params->toJson();
        $keyHash = hash('sha256', $raw);
        $lockKey = 'idempotency:'.$user->id.':'.$keyHash.':'.hash('sha256', $scope);

        $callback = function () use ($request, $next, $user, $scope, $keyHash) {
            $existing = IdempotentRequest::query()
                ->where('user_id', $user->id)
                ->where('scope', $scope)
                ->where('key_hash', $keyHash)
                ->whereBetween('status_code', [200, 299])
                ->whereNotNull('response_body')
                ->first();

            if ($existing) {
                $decoded = json_decode((string) $existing->response_body, true);

                return response()->json(
                    is_array($decoded) ? $decoded : ['data' => $existing->response_body],
                    $existing->status_code,
                    ['X-Idempotent-Replayed' => 'true'],
                );
            }

            /** @var Response $response */
            $response = $next($request);

            $code = $response->getStatusCode();
            if ($code >= 200 && $code < 300) {
                $row = IdempotentRequest::firstOrNew([
                    'user_id' => $user->id,
                    'scope' => $scope,
                    'key_hash' => $keyHash,
                ]);
                $row->status_code = $code;
                $row->response_body = $response->getContent();
                $row->save();
            }

            return $response;
        };

        try {
            return Cache::lock($lockKey, 30)->block(15, $callback);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            return $callback();
        }
    }
}
