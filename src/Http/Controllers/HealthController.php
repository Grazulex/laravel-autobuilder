<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __construct(
        protected BrickRegistry $registry
    ) {}

    /**
     * Basic health check - returns 200 if the service is running.
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'autobuilder',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed health check with component status.
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'bricks' => $this->checkBricks(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'service' => 'autobuilder',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Get AutoBuilder statistics.
     */
    public function stats(): JsonResponse
    {
        $flowStats = $this->getFlowStats();
        $runStats = $this->getRunStats();
        $brickStats = $this->getBrickStats();

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'statistics' => [
                'flows' => $flowStats,
                'runs' => $runStats,
                'bricks' => $brickStats,
            ],
        ]);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            // Check if tables exist
            $flowsExist = DB::getSchemaBuilder()->hasTable('autobuilder_flows');
            $runsExist = DB::getSchemaBuilder()->hasTable('autobuilder_flow_runs');

            if (! $flowsExist || ! $runsExist) {
                return [
                    'status' => 'error',
                    'message' => 'Required tables not found',
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'autobuilder_health_check_'.uniqid();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== true) {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write failed',
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Cache is working',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkBricks(): array
    {
        try {
            $triggers = $this->registry->getTriggers();
            $conditions = $this->registry->getConditions();
            $actions = $this->registry->getActions();

            $total = count($triggers) + count($conditions) + count($actions);

            if ($total === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'No bricks registered',
                ];
            }

            return [
                'status' => 'ok',
                'message' => "{$total} bricks registered",
                'counts' => [
                    'triggers' => count($triggers),
                    'conditions' => count($conditions),
                    'actions' => count($actions),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Brick registry check failed: '.$e->getMessage(),
            ];
        }
    }

    protected function getFlowStats(): array
    {
        return [
            'total' => Flow::count(),
            'active' => Flow::where('active', true)->count(),
            'inactive' => Flow::where('active', false)->count(),
        ];
    }

    protected function getRunStats(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();

        return [
            'total' => FlowRun::count(),
            'today' => FlowRun::where('created_at', '>=', $today)->count(),
            'this_week' => FlowRun::where('created_at', '>=', $thisWeek)->count(),
            'by_status' => [
                'completed' => FlowRun::where('status', 'completed')->count(),
                'failed' => FlowRun::where('status', 'failed')->count(),
                'running' => FlowRun::where('status', 'running')->count(),
                'pending' => FlowRun::where('status', 'pending')->count(),
            ],
        ];
    }

    protected function getBrickStats(): array
    {
        return [
            'triggers' => count($this->registry->getTriggers()),
            'conditions' => count($this->registry->getConditions()),
            'actions' => count($this->registry->getActions()),
        ];
    }
}
