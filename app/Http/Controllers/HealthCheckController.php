<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    public function check(): JsonResponse
    {
        $dbStatus = false;
        try {
            DB::connection()->getPdo();
            $dbStatus = true;
        } catch (Throwable $e) {
            $dbStatus = false;
        }

        $storageWritable = is_writable(storage_path());

        $status = $dbStatus && $storageWritable ? 'HEALTHY' : 'UNHEALTHY';
        $code = $status === 'HEALTHY' ? 200 : 503;

        return ApiResponse::success([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus ? 'connected' : 'disconnected',
                'storage' => $storageWritable ? 'writable' : 'read-only',
            ],
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
        ], "Estado del sistema: {$status}", $code);
    }
}
