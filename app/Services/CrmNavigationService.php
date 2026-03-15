<?php

namespace App\Services;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;

class CrmNavigationService
{
    public function build(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user?->hasRole(RoleEnum::ADMIN->value) ?? false;
        $isLegalOfficer = $user?->hasRole(RoleEnum::LEGAL_OFFICER->value) ?? false;
        $canViewLegalPage = $user?->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::LEADER->value,
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEGAL_OFFICER->value,
        ]) ?? false;
        $showLoanOnlyNavigation = ($user?->hasRole(RoleEnum::LOAN_OFFICER->value) ?? false) && ! $isAdmin;
        $showLegalOnlyNavigation = $isLegalOfficer && ! $isAdmin;
        $canViewDashboard = $isAdmin || ($user?->hasAnyRole([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
            RoleEnum::LOAN_OFFICER->value,
            RoleEnum::LEGAL_OFFICER->value,
        ]) ?? false);

        $dashboardRoute = 'dashboard.index';
        $homeRoute = $isLegalOfficer
            ? 'legals.index'
            : ($showLoanOnlyNavigation
                ? 'loans.borrower-profile'
                : ($canViewDashboard ? $dashboardRoute : 'leads.index'));

        $links = [];

        if (! $showLoanOnlyNavigation && ! $showLegalOnlyNavigation) {
            if ($canViewDashboard) {
                $links[] = [
                    'label' => 'Dashboard',
                    'route' => 'dashboard.index',
                    'active' => 'dashboard.*',
                    'icon' => 'fa-solid fa-chart-pie',
                ];
            }
            $links[] = [
                'label' => 'Leads',
                'route' => 'leads.index',
                'active' => 'leads.*',
                'icon' => 'fa-solid fa-bullseye',
            ];
            $links[] = [
                'label' => 'Deals',
                'route' => 'deals.index',
                'active' => 'deals.*',
                'icon' => 'fa-solid fa-briefcase',
            ];
        }

        if (! $showLegalOnlyNavigation) {
            $links[] = [
                'label' => 'Loans',
                'route' => null,
                'active' => 'loans.*',
                'icon' => 'fa-solid fa-hand-holding-dollar',
                'children' => [
                    [
                        'label' => 'Borrower Profile',
                        'route' => 'loans.borrower-profile',
                        'active' => 'loans.borrower-profile*',
                        'badge_key' => 'borrower_profile',
                        'badge' => 0,
                    ],
                    [
                        'label' => 'Pre-Qualification',
                        'route' => 'loans.pre-qualification',
                        'active' => 'loans.pre-qualification*',
                    ],
                    [
                        'label' => 'Bank Submission',
                        'route' => 'loans.bank-submission-tracking',
                        'active' => 'loans.bank-submission-tracking*',
                        'badge_key' => 'bank_submission',
                        'badge' => 0,
                    ],
                    [
                        'label' => 'Approval Analysis',
                        'route' => 'loans.approval-analysis',
                        'active' => 'loans.approval-analysis*',
                    ],
                    [
                        'label' => 'Disbursement',
                        'route' => 'loans.disbursement',
                        'active' => 'loans.disbursement*',
                    ],
                ],
            ];
        }

        if (! $showLoanOnlyNavigation) {
            if ($canViewLegalPage) {
                $links[] = [
                    'label' => 'Legal',
                    'route' => 'legals.index',
                    'active' => 'legals.*',
                    'icon' => 'fa-solid fa-scale-balanced',
                    'badge_key' => 'legal_new',
                    'badge' => 0,
                ];
            }

            if (! $showLegalOnlyNavigation) {
                $links[] = [
                    'label' => 'Commission',
                    'route' => 'commissions.index',
                    'active' => 'commissions.*',
                    'icon' => 'fa-solid fa-coins',
                ];

                $links[] = [
                    'label' => 'Clients',
                    'route' => 'clients.index',
                    'active' => 'clients.*',
                    'icon' => 'fa-solid fa-users',
                ];

                if ($isAdmin) {
                    $links[] = [
                        'label' => 'Users',
                        'route' => 'users.index',
                        'active' => 'users.*',
                        'icon' => 'fa-solid fa-user-gear',
                    ];
                }
            }
        }

        foreach ($links as $index => $item) {
            $links[$index]['expanded'] = false;
            $children = $item['children'] ?? [];
            if (! empty($children)) {
                foreach ($children as $child) {
                    if ($request->routeIs($child['active'])) {
                        $links[$index]['expanded'] = true;
                        break;
                    }
                }
            }
        }

        return [
            'homeUrl' => route($homeRoute),
            'links' => array_map(fn ($item) => $this->normalizeLink($request, $item), $links),
        ];
    }

    private function normalizeLink(Request $request, array $item): array
    {
        $href = $item['route'] ? route($item['route']) : null;
        $children = $item['children'] ?? [];

        return [
            'label' => $item['label'],
            'href' => $href,
            'icon' => $item['icon'] ?? null,
            'active' => $request->routeIs($item['active'] ?? '') ?? false,
            'expanded' => (bool) ($item['expanded'] ?? false),
            'badgeKey' => $item['badge_key'] ?? null,
            'badge' => (int) ($item['badge'] ?? 0),
            'children' => array_map(function ($child) use ($request) {
                return [
                    'label' => $child['label'],
                    'href' => route($child['route']),
                    'active' => $request->routeIs($child['active'] ?? ''),
                    'badgeKey' => $child['badge_key'] ?? null,
                    'badge' => (int) ($child['badge'] ?? 0),
                ];
            }, $children),
        ];
    }
}
