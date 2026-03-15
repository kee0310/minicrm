<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\MonthFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if(! $user, 403);

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $this->renderDashboard($request, 'deals');
        }

        if ($user->hasAnyRole([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
        ])) {
            return $this->renderDashboard($request, 'sales');
        }

        abort(403);
    }

    public function sales(Request $request)
    {
        return $this->renderDashboard($request, 'sales');
    }

    public function deals(Request $request)
    {
        return $this->renderDashboard($request, 'deals');
    }

    public function salespeople(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if(! $user, 403);

        abort_unless(
            $user->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LEADER->value]),
            403
        );

        $selectedMonth = MonthFilter::resolve($request->query('month'));
        $viewData = $this->dashboardService->salespeopleData($request, $user, $selectedMonth);

        return Inertia::render('dashboard/salespeople', $viewData);
    }

    private function renderDashboard(Request $request, string $dashboardMode)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if(! $user, 403);

        if ($dashboardMode === 'sales') {
            abort_unless(
                $user->hasAnyRole([
                    RoleEnum::SALESPERSON->value,
                    RoleEnum::LEADER->value,
                ]),
                403
            );
        } else {
            abort_unless($user->hasRole(RoleEnum::ADMIN->value), 403);
        }

        $selectedMonth = MonthFilter::resolve($request->query('month'));
        $cacheKey = $this->dashboardCacheKey($user, $dashboardMode, $selectedMonth);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return Inertia::render('dashboard/index', $cached);
        }
        $viewData = $this->dashboardService->buildDashboardViewData($request, $user, $dashboardMode, $selectedMonth);

        Cache::put($cacheKey, $viewData, now()->addSeconds(120));

        return Inertia::render('dashboard/index', $viewData);
    }

    protected function dashboardCacheKey(User $user, string $dashboardMode, Carbon $selectedMonth): string
    {
        $roleFingerprint = md5($user->getRoleNames()->sort()->implode('|'));

        return sprintf(
            'dashboard:%s:user:%d:roles:%s:mode:%s:month:%s',
            app()->environment(),
            $user->id,
            $roleFingerprint,
            $dashboardMode,
            $selectedMonth->format('Y-m')
        );
    }
}
