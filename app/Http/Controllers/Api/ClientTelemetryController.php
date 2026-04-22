<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientTelemetryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'level' => 'required|string|in:debug,info,warn,error',
            'message' => 'required|string|max:500',
            'context' => 'nullable|array',
            't' => 'nullable|integer',
            'href' => 'nullable|string|max:2000',
        ]);

        $payload = [
            'message' => $data['message'],
            'href' => $data['href'] ?? null,
            'context' => is_array($data['context'] ?? null) ? $data['context'] : [],
            'client_ts' => $data['t'] ?? null,
        ];

        match ($data['level']) {
            'error' => Log::error('frontend.telemetry', $payload),
            'warn' => Log::warning('frontend.telemetry', $payload),
            default => Log::info('frontend.telemetry', $payload),
        };

        return response()->json(['ok' => true]);
    }
}
