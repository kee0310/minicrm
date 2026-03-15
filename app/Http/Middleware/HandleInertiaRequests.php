<?php

namespace App\Http\Middleware;

use App\Services\CrmNavigationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $navigation = app(CrmNavigationService::class)->build($request);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'crm' => [
                'navigation' => $navigation,
                'routeName' => $request->route()?->getName(),
                'urls' => [
                    'loanDetailDeal' => url('/loans/detail/__DEAL__'),
                    'loanDetailLoan' => url('/loans/detail/by-loan/__LOAN__'),
                    'notificationsCount' => route('notifications.count'),
                ],
                'flash' => [
                    'success' => session('success'),
                    'warning' => session('warning'),
                    'error' => session('error'),
                    'deleted' => session('deleted'),
                ],
            ],
        ];
    }
}
