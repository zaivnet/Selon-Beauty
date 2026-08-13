<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        return response()->json([
            'status' => $dbStatus === 'ok' ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'database' => $dbStatus,
            'timestamp' => now()->toIso8601String(),
        ], $dbStatus === 'ok' ? 200 : 503);
    }
}
