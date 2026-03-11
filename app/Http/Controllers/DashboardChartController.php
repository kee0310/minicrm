<?php

namespace App\Http\Controllers;

use App\Enums\PipelineEnum;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardChartController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function __invoke(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if(! $user, 403);

        $mode = (string) $request->query('mode', 'sales');
        $this->dashboardService->authorizeDashboardMode($user, $mode);

        $monthParam = (string) $request->query('month', '');
        $selectedMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $cacheKey = sprintf(
            'dashboard:charts:user:%d:roles:%s:mode:%s:month:%s',
            $user->id,
            md5($user->getRoleNames()->sort()->implode('|')),
            $mode,
            $selectedMonth->format('Y-m')
        );

        $payload = Cache::remember($cacheKey, now()->addSeconds(120), function () use ($user, $mode, $selectedMonth): array {
            return $this->dashboardService->buildChartPayload($user, $mode, $selectedMonth);
        });

        return response()->json(['data' => $payload]);
    }

    public function pipelineDetails(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if(! $user, 403);

        $mode = (string) $request->query('mode', 'sales');
        $this->dashboardService->authorizeDashboardMode($user, $mode);

        $monthParam = (string) $request->query('month', '');
        $selectedMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $stageParam = (string) $request->query('stage', '');
        $stageEnum = PipelineEnum::tryFrom($stageParam);
        abort_if(! $stageEnum, 422, 'Invalid pipeline stage.');

        $cacheKey = sprintf(
            'dashboard:pipeline-details:user:%d:roles:%s:mode:%s:month:%s:stage:%s',
            $user->id,
            md5($user->getRoleNames()->sort()->implode('|')),
            $mode,
            $selectedMonth->format('Y-m'),
            md5($stageEnum->value)
        );

        $payload = Cache::remember($cacheKey, now()->addSeconds(120), function () use ($user, $mode, $selectedMonth, $stageEnum): array {
            return $this->dashboardService->buildPipelineDetailsPayload($user, $mode, $selectedMonth, $stageEnum);
        });

        return response()->json(['data' => $payload]);
    }
}
