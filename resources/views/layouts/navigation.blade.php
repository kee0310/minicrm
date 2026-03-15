@php
    // Route setup for sidebar visibility and active states.
    $user = auth()->user();
    $loanNavBadges = $loanNavBadges ?? [];
    $legalNavBadges = $legalNavBadges ?? [];
    $isAdmin = $user?->hasRole(\App\Enums\RoleEnum::ADMIN->value);
    $isLegalOfficer = $user?->hasRole(\App\Enums\RoleEnum::LEGAL_OFFICER->value);
    $canViewLegalPage = $user?->hasAnyRole([
        \App\Enums\RoleEnum::ADMIN->value,
        \App\Enums\RoleEnum::LEADER->value,
        \App\Enums\RoleEnum::SALESPERSON->value,
        \App\Enums\RoleEnum::LEGAL_OFFICER->value,
    ]);
    $showLoanOnlyNavigation = $user?->hasRole(\App\Enums\RoleEnum::LOAN_OFFICER->value) && !$isAdmin;
    $showLegalOnlyNavigation = $isLegalOfficer && !$isAdmin;
    $canViewDashboard =
        $isAdmin ||
        $user?->hasAnyRole([
            \App\Enums\RoleEnum::SALESPERSON->value,
            \App\Enums\RoleEnum::LEADER->value,
            \App\Enums\RoleEnum::LOAN_OFFICER->value,
            \App\Enums\RoleEnum::LEGAL_OFFICER->value,
        ]);
    $dashboardRoute = 'dashboard.index';
    $homeRoute = $isLegalOfficer
        ? 'legals.index'
        : ($showLoanOnlyNavigation
            ? 'loans.borrower-profile'
            : ($canViewDashboard
                ? $dashboardRoute
                : 'leads.index'));

    $links = [];
    $isLoanRoute = request()->routeIs('loans.*');

    if (!$showLoanOnlyNavigation && !$showLegalOnlyNavigation) {
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

    if (!$showLegalOnlyNavigation) {
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
                    'badge' => (int) ($loanNavBadges['borrower_profile'] ?? 0),
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
                    'badge' => (int) ($loanNavBadges['bank_submission'] ?? 0),
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
    if (!$showLoanOnlyNavigation) {
        if ($canViewLegalPage) {
            $links[] = [
                'label' => 'Legal',
                'route' => 'legals.index',
                'active' => 'legals.*',
                'icon' => 'fa-solid fa-scale-balanced',
                'badge_key' => 'legal_new',
                'badge' => (int) ($legalNavBadges['legal_new'] ?? 0),
            ];
        }

        if (!$showLegalOnlyNavigation) {
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
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (request()->routeIs($child['active'])) {
                    $links[$index]['expanded'] = true;
                    break;
                }
            }
        }
    }
@endphp

{{-- Desktop sidebar navigation --}}
<aside
    class="crm-sidebar-desktop fixed inset-y-0 left-0 z-40 hidden border-r border-[#395085] bg-gradient-to-b from-[#233b6d] via-[#325baf] to-[#0c8dc8] text-slate-100 lg:flex">
    <div class="flex h-full w-full flex-col">
        <div class="flex items-center justify-between border-b border-white/15 px-3 py-3">
            <a href="{{ route($homeRoute) }}" class="crm-sidebar-brand" x-show="sidebarExpanded"
                x-transition.opacity.duration.120ms>
                <span class="crm-nav-icon crm-sidebar-brand-icon text-xl"><i
                        class="fa-light fa-house-building"></i></span>
                <span class="font-bold tracking-wide text-white truncate">CRM Property</span>
            </a>
            <button type="button" class="crm-sidebar-toggle-btn"
                @click="toggleSidebar(); window.dispatchEvent(new CustomEvent('crm-sidebar-toggled'))">
                <i data-sidebar-toggle-expanded class="fa-solid fa-angles-left"></i>
                <i data-sidebar-toggle-collapsed class="fa-solid fa-bars"></i>
            </button>
        </div>

        <nav class="flex-1 space-y-1 p-2 scrollbar-hide transition-all" x-data="sidebar()" x-init="init()">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['active']); @endphp
                @if (!empty($link['children']))
                    <div x-data="{ submenu: {{ $link['expanded'] ? 'true' : 'false' }}, flyout: false }" class="relative" @mouseenter="if (!sidebarExpanded) flyout = true"
                        @mouseleave="if (!sidebarExpanded) flyout = false">


                        <button type="button"
                            class="crm-nav-item group justify-between py-2 {{ $active ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}"
                            @click="sidebarExpanded ? submenu = !submenu : flyout = !flyout">
                            <span class="flex items-center gap-3">
                                <i class="{{ $link['icon'] }} crm-nav-icon text-base"></i>
                                <span x-show="sidebarExpanded" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="-translate-x-5 opacity-0"
                                    x-transition:enter-end="translate-x-0 opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="translate-x-0 opacity-100"
                                    x-transition:leave-end="-translate-x-5 opacity-0"
                                    class="truncate whitespace-nowrap">
                                    {{ $link['label'] }}
                                </span>
                            </span>
                            <i class="fa-solid fa-chevron-down crm-nav-icon crm-nav-icon-sm text-xs crm-nav-chevron"
                                x-show="sidebarExpanded" :class="{ 'is-open': submenu }"></i>
                        </button>

                        <!-- Expanded submenu -->
                        <div x-cloak x-show="sidebarExpanded && submenu"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="-translate-x-5 opacity-0"
                            x-transition:enter-end="translate-x-0 opacity-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="translate-x-0 opacity-100"
                            x-transition:leave-end="-translate-x-5 opacity-0" class="crm-nav-submenu origin-left mt-1">
                            @foreach ($link['children'] as $child)
                                <a href="{{ route($child['route']) }}"
                                    class="crm-nav-subitem {{ request()->routeIs($child['active']) ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}">
                                    <span class="truncate">{{ $child['label'] }}</span>
                                    @if (!empty($child['badge_key']))
                                        <span data-nav-badge-key="{{ $child['badge_key'] }}"
                                            class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[9px] font-semibold leading-none text-white {{ ($child['badge'] ?? 0) > 0 ? '' : 'hidden' }}">
                                            {{ ($child['badge'] ?? 0) > 99 ? '99+' : $child['badge'] ?? 0 }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <!-- Flyout submenu when collapsed -->
                        <div x-cloak x-show="!sidebarExpanded && flyout"
                            x-transition:enter="transition-all ease-out duration-300"
                            x-transition:enter-start="max-h-0 opacity-0 scale-y-0"
                            x-transition:enter-end="max-h-96 opacity-100 scale-y-100"
                            x-transition:leave="transition-all ease-in duration-200"
                            x-transition:leave-start="max-h-96 opacity-100 scale-y-100"
                            x-transition:leave-end="max-h-0 opacity-0 scale-y-0"
                            class="crm-nav-flyout origin-top absolute left-full top-0 ml-2 w-56 rounded bg-[#233b6d] shadow-lg">
                            <p class="mb-2 text-sm font-semibold text-white">{{ $link['label'] }}</p>
                            <div class="space-y-1">
                                @foreach ($link['children'] as $child)
                                    <a href="{{ route($child['route']) }}"
                                        class="crm-nav-subitem {{ request()->routeIs($child['active']) ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}">
                                        <span class="truncate">{{ $child['label'] }}</span>
                                        @if (!empty($child['badge_key']))
                                            <span data-nav-badge-key="{{ $child['badge_key'] }}"
                                                class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white {{ ($child['badge'] ?? 0) > 0 ? '' : 'hidden' }}">
                                                {{ ($child['badge'] ?? 0) > 99 ? '99+' : $child['badge'] ?? 0 }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route($link['route']) }}"
                        class="crm-nav-item group py-3 {{ $active ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}">
                        <i class="{{ $link['icon'] }} crm-nav-icon text-base"></i>
                        <!-- Animate only the text -->
                        <span x-show="sidebarExpanded" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="-translate-x-5 opacity-0"
                            x-transition:enter-end="translate-x-0 opacity-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="translate-x-0 opacity-100"
                            x-transition:leave-end="-translate-x-5 opacity-0" class="truncate whitespace-nowrap">
                            {{ $link['label'] }}
                        </span>
                        @if (!empty($link['badge_key']))
                            <span data-nav-badge-key="{{ $link['badge_key'] }}"
                                class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white {{ ($link['badge'] ?? 0) > 0 ? '' : 'hidden' }}">
                                {{ ($link['badge'] ?? 0) > 99 ? '99+' : $link['badge'] ?? 0 }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
</aside>

{{-- Mobile sidebar navigation --}}
<div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/40" @click="sidebarOpen = false"></div>
    <aside
        class="absolute inset-y-0 left-0 flex w-[18rem] max-w-[85vw] flex-col border-r border-[#395085] bg-gradient-to-b from-[#233b6d] via-[#325baf] to-[#0c8dc8] text-slate-100 shadow-xl">
        <div class="flex h-16 items-center justify-between border-b border-white/15 px-4">
            <a href="{{ route($homeRoute) }}" class="crm-sidebar-brand">
                <i class="fa-solid fa-chart-line crm-nav-icon crm-sidebar-brand-icon"></i>
                <span class="truncate text-sm font-semibold tracking-wide text-white">Mini CRM</span>
            </a>
            <button type="button" class="crm-sidebar-toggle-btn" @click="sidebarOpen = false">
                <i class="fa-solid fa-xmark crm-nav-icon"></i>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-2 scrollbar-hide" x-data="{ loanOpen: {{ $isLoanRoute ? 'true' : 'false' }} }">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['active']); @endphp
                @if (!empty($link['children']))
                    <button type="button"
                        class="crm-nav-item group justify-between {{ $active ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}"
                        @click="loanOpen = !loanOpen">
                        <span class="flex items-center gap-3">
                            <i class="{{ $link['icon'] }} crm-nav-icon text-base"></i>
                            <span class="truncate whitespace-nowrap">{{ $link['label'] }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down crm-nav-icon crm-nav-icon-sm text-xs crm-nav-chevron"
                            :class="{ 'is-open': loanOpen }"></i>
                    </button>
                    <div x-show="loanOpen" class="crm-nav-submenu">
                        @foreach ($link['children'] as $child)
                            @php $childActive = request()->routeIs($child['active']); @endphp
                            <a href="{{ route($child['route']) }}"
                                class="crm-nav-subitem {{ $childActive ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}"
                                @click="sidebarOpen = false">
                                <span class="truncate">{{ $child['label'] }}</span>
                                @if (!empty($child['badge_key']))
                                    <span data-nav-badge-key="{{ $child['badge_key'] }}"
                                        class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white {{ ($child['badge'] ?? 0) > 0 ? '' : 'hidden' }}">
                                        {{ ($child['badge'] ?? 0) > 99 ? '99+' : $child['badge'] ?? 0 }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route($link['route']) }}"
                        class="crm-nav-item group py-3 {{ $active ? 'crm-nav-item-active' : 'crm-nav-item-inactive' }}"
                        @click="sidebarOpen = false">
                        <i class="{{ $link['icon'] }} crm-nav-icon text-base"></i>
                        <span class="truncate whitespace-nowrap">{{ $link['label'] }}</span>
                        @if (!empty($link['badge_key']))
                            <span data-nav-badge-key="{{ $link['badge_key'] }}"
                                class="ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white {{ ($link['badge'] ?? 0) > 0 ? '' : 'hidden' }}">
                                {{ ($link['badge'] ?? 0) > 99 ? '99+' : $link['badge'] ?? 0 }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>
    </aside>
</div>
