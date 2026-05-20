<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function ok(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    protected function accepted(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 202);
    }
}
