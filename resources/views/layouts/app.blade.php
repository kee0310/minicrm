<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <link rel="preload" href="{{ asset('vendor/fontAwesomePro-6.7.1/webfonts/fa-solid-900.woff2') }}" as="font"
        type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('vendor/fontAwesomePro-6.7.1/webfonts/fa-light-300.woff2') }}" as="font"
        type="font/woff2" crossorigin>
    <link href="{{ asset('vendor/fontAwesomePro-6.7.1/all.css') }}" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            try {
                var rawScrollState = sessionStorage.getItem('crm.list.scroll.state');
                var navEntries = (performance.getEntriesByType && performance.getEntriesByType('navigation')) || [];
                var navType = navEntries[0] && navEntries[0].type;
                var isReloadNav = navType === 'reload';
                if (isReloadNav) {
                    sessionStorage.removeItem('crm.list.scroll.state');
                    document.documentElement.setAttribute('data-pending-table-scroll', '0');
                }
                if (rawScrollState) {
                    var parsedScrollState = JSON.parse(rawScrollState);
                    var isSamePath = parsedScrollState && parsedScrollState.pathname === window.location.pathname;
                    var isFresh = parsedScrollState && (Date.now() - Number(parsedScrollState.timestamp || 0) < 15000);
                    var isPreserveMode = !parsedScrollState || parsedScrollState.mode !== 'table-top';
                    if (!isReloadNav && isSamePath && isFresh && !isPreserveMode) {
                        document.documentElement.setAttribute('data-pending-table-scroll', '1');
                    } else {
                        document.documentElement.setAttribute('data-pending-table-scroll', '0');
                    }
                } else {
                    document.documentElement.setAttribute('data-pending-table-scroll', '0');
                }
            } catch (_error) {}

            var saved = localStorage.getItem('crm.sidebar.expanded');
            var expanded = saved !== '0';
            document.documentElement.setAttribute('data-sidebar-expanded', expanded ? '1' : '0');
            document.documentElement.setAttribute('data-sidebar-transition', '0');
            document.documentElement.setAttribute('data-sidebar-direction', 'idle');
            document.documentElement.style.setProperty('--crm-initial-sidebar-width', expanded ? '14rem' : '4rem');
            window.__crmSidebarExpanded = expanded;
            window.__crmLoanDetailDealUrl = @json(url('/loans/detail/__DEAL__'));
            window.__crmLoanDetailLoanUrl = @json(url('/loans/detail/by-loan/__LOAN__'));
            window.__crmNotificationsCountUrl = @json(route('notifications.count'));
        })();
    </script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'][data-sidebar-transition='0'] .crm-sidebar-desktop [data-sidebar-chevron] {
            display: none !important;
        }

        html[data-sidebar-expanded='1'] .crm-sidebar-desktop [data-sidebar-toggle-expanded] {
            display: inline-block !important;
        }

        html[data-sidebar-expanded='1'] .crm-sidebar-desktop [data-sidebar-toggle-collapsed] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'] .crm-sidebar-desktop [data-sidebar-toggle-expanded] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'] .crm-sidebar-desktop [data-sidebar-toggle-collapsed] {
            display: inline-block !important;
        }

        html[data-pending-table-scroll='1'] .crm-main-with-sidebar {
            visibility: hidden;
        }
    </style>
</head>

<body>
    <div x-data="{
        sidebarOpen: false,
        sidebarExpanded: (window.__crmSidebarExpanded ?? true),
        sidebarTransitioning: false,
        sidebarTransitionTimer: null,
        initSidebarState() {
            const saved = localStorage.getItem('crm.sidebar.expanded');
            if (saved === '0') {
                this.sidebarExpanded = false;
            } else if (saved === '1') {
                this.sidebarExpanded = true;
            } else {
                this.sidebarExpanded = true;
            }
            document.documentElement.setAttribute('data-sidebar-expanded', this.sidebarExpanded ? '1' : '0');
            this.$nextTick(() => $el.classList.add('crm-ready'));
        },
        toggleSidebar() {
            const nextExpanded = !this.sidebarExpanded;
            document.documentElement.setAttribute('data-sidebar-transition', '1');
            document.documentElement.setAttribute('data-sidebar-direction', nextExpanded ? 'expand' : 'collapse');
            this.sidebarTransitioning = true;
            this.sidebarExpanded = nextExpanded;
            localStorage.setItem('crm.sidebar.expanded', this.sidebarExpanded ? '1' : '0');
            document.documentElement.setAttribute('data-sidebar-expanded', this.sidebarExpanded ? '1' : '0');
            if (this.sidebarTransitionTimer) {
                clearTimeout(this.sidebarTransitionTimer);
            }
            this.sidebarTransitionTimer = setTimeout(() => {
                document.documentElement.setAttribute('data-sidebar-transition', '0');
                document.documentElement.setAttribute('data-sidebar-direction', 'idle');
                this.sidebarTransitioning = false;
            }, 320);
        }
    }" x-init="initSidebarState()"
        style="--crm-sidebar-width: var(--crm-initial-sidebar-width, 14rem);"
        :style="`--crm-sidebar-width: ${sidebarExpanded ? '14rem' : '4rem'}`" class="crm-shell lg:flex">
        @include('layouts.navigation')
        <div class="pointer-events-none fixed left-1/2 top-[80px] z-[100] w-full max-w-2xl -translate-x-1/2 px-4">
            <x-flash-messages />
        </div>

        <div class="crm-main-with-sidebar min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                @php
                    $routeName = request()->route()?->getName();
                    $headerSubtitleMap = [
                        'dashboard.index' => 'Executive and operational overview',
                        'leads.index' => 'Track and manage every incoming lead',
                        'deals.index' => 'Monitor deal progress across the pipeline',
                        'clients.index' => 'Manage client records and relationships',
                        'clients.show' => 'View client details and related deals',
                        'users.index' => 'Control user access and team roles',
                        'commissions.index' => 'Review commission eligibility and payouts',
                        'legals.index' => 'Track legal progress and milestones',
                        'profile.edit' => 'Manage your account settings and security',
                        'loans.pre-qualification' => 'Assess loan readiness before submission',
                        'loans.borrower-profile' => 'Review borrower credit and financial profile',
                        'loans.bank-submission-tracking' => 'Track loan submissions and approval status',
                        'loans.approval-analysis' => 'Compare approved terms across applications',
                        'loans.disbursement' => 'Track post-approval disbursement milestones',
                    ];
                    $resolvedHeaderSubtitle = trim(
                        (string) ($headerSubtitle ??
                            ($headerSubtitleMap[$routeName] ?? 'Manage and track your CRM operations')),
                    );
                @endphp
                <div class="crm-content py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" class="crm-btn-secondary px-3 py-2 lg:hidden"
                                @click="sidebarOpen = !sidebarOpen">
                                <span>Menu</span>
                            </button>
                            <div class="min-w-0">
                                @isset($header)
                                    {{ $header }}
                                @else
                                    <h2 class="text-lg font-semibold text-slate-900">{{ config('app.name', 'CRM') }}</h2>
                                @endisset
                                @if ($resolvedHeaderSubtitle !== '')
                                    <p class="mt-1 text-sm text-slate-500">{{ $resolvedHeaderSubtitle }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right ">
                                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                            </div>
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-50">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                            Log Out
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </div>
            </header>

            <main class="crm-content">
                {{ $slot }}
            </main>
        </div>
    </div>
    <i class="fa-solid fa-arrow-up"></i>
    <script>
        (function() {
            const upIcon = '▲';
            const downIcon = '▼';

            const normalizeSortIndicators = () => {
                document.querySelectorAll('[data-sort-indicator]').forEach((indicator) => {
                    if (indicator.querySelector('i')) {
                        return;
                    }
                    const raw = (indicator.textContent || '').trim();
                    if (raw === '<>') {
                        indicator.innerHTML = '';
                        return;
                    }
                    if (raw === '^') {
                        indicator.textContent = upIcon;
                        return;
                    }
                    if (raw === 'v') {
                        indicator.textContent = downIcon;
                    }
                });
            };

            const scheduleNormalize = () => window.requestAnimationFrame(normalizeSortIndicators);

            document.addEventListener('click', (event) => {
                if (event.target.closest('th[data-sort-index]')) {
                    scheduleNormalize();
                }
            });

            const observer = new MutationObserver(scheduleNormalize);
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true,
            });

            document.addEventListener('DOMContentLoaded', normalizeSortIndicators);
            normalizeSortIndicators();
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const badgeNodes = Array.from(document.querySelectorAll('[data-nav-badge-key]'));
            if (badgeNodes.length === 0) {
                return;
            }

            const endpoint = window.__crmNotificationsCountUrl || '/notifications/count';
            let active = !document.hidden && document.hasFocus();
            let loading = false;

            const setBadgeCount = (key, count) => {
                const safeCount = Number.isFinite(count) ? Math.max(0, Math.floor(count)) : 0;
                const label = safeCount > 99 ? '99+' : String(safeCount);

                badgeNodes.forEach((node) => {
                    if (node.getAttribute('data-nav-badge-key') !== key) {
                        return;
                    }

                    if (safeCount > 0) {
                        node.textContent = label;
                        node.classList.remove('hidden');
                    } else {
                        node.textContent = '0';
                        node.classList.add('hidden');
                    }
                });
            };

            const loadNotifications = () => {
                if (loading) {
                    return;
                }

                loading = true;

                fetch(endpoint, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    })
                    .then((response) => (response.ok ? response.json() : {}))
                    .then((payload) => {
                        setBadgeCount('borrower_profile', Number(payload.borrower_profile ?? 0));
                        setBadgeCount('bank_submission', Number(payload.bank_submission ?? payload.loan_submission ?? 0));
                        setBadgeCount('legal_new', Number(payload.legal_new ?? payload.legal ?? 0));
                    })
                    .catch(() => {})
                    .finally(() => {
                        loading = false;
                    });
            };

            window.addEventListener('blur', () => {
                active = false;
            });

            window.addEventListener('focus', () => {
                active = true;
                loadNotifications();
            });

            document.addEventListener('visibilitychange', () => {
                active = !document.hidden && document.hasFocus();
                if (active) {
                    loadNotifications();
                }
            });

            loadNotifications();

            setInterval(() => {
                if (active) {
                    loadNotifications();
                }
            }, 60000);
        });
    </script>

</body>

</html>
