<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Check the health of the application and its dependencies.
     */
    public function __invoke()
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'redis' => $this->checkRedis(),
            ],
        ];

        $overallHealthy = collect($status['services'])->every(fn($s) => $s['status'] === 'ok');

        if (!$overallHealthy) {
            $status['status'] = 'unhealthy';
            return response()->json($status, 503);
        }

        return response()->json($status);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('health_check', 'ok', 10);
            if (Cache::get('health_check') === 'ok') {
                return ['status' => 'ok'];
            }
            return ['status' => 'error', 'message' => 'Cache verify failed'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis()
    {
        try {
            $redis = Redis::connection();
            $redis->ping();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
