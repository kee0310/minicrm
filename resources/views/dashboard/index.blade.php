<x-app-layout>
    @php
        $headerSubtitle = $dashboardSubtitle ?? 'Executive and operational overview';
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $dashboardTitle ?? 'CRM Dashboard' }}</h2>
            </div>
        </div>
    </x-slot>

    @php
        $dashboardData = [
            'chartEndpoint' => $dashboardChartsEndpoint ?? null,
            'pipelineDetailsEndpoint' => $dashboardPipelineDetailsEndpoint ?? null,
            'monthLabel' => $monthNav['label'] ?? '',
            'totalLeadsMonth' => (int) ($executive['new_leads_month'] ?? 0),
            'salesPerformance' => null,
            'commissionTrend' => null,
            'leadsBySource' => [],
            'pipelineStages' => [],
        ];
    @endphp
    <div id="dashboard-data" class="hidden" aria-hidden="true" data-dashboard='@json($dashboardData)'></div>

    <div id="dashboard-loading"
        class="fixed inset-0 z-1000 flex items-center justify-center bg-white/70 backdrop-blur-sm">
        <div
            class="flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-sm text-slate-500 shadow-sm">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600"></span>
            <span>Loading dashboard...</span>
        </div>
    </div>
    <div class="relative space-y-4 crm-dashboard-scroll">
        <!-- Month Nav -->
        <div class="flex justify-end">
            @include('dashboard.partials.month-nav')
        </div>

        <!-- KPIs -->
        <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @include('dashboard.partials.kpis')
        </section>

        <!-- Charts & Reports -->
        <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-dashboard.chart-card title="Leads by Source" class="crm-anim-pop" style="--crm-anim-delay: 120ms;">
                <div id="dashboard-leads-source"></div>
            </x-dashboard.chart-card>

            <x-dashboard.chart-card title="Pipeline Overview" class="crm-anim-pop" style="--crm-anim-delay: 180ms;">
                <div id="dashboard-pipeline-overview"></div>
            </x-dashboard.chart-card>

            <x-dashboard.chart-card title="Total Commission (Past 5 Months)" class="crm-anim-pop"
                style="--crm-anim-delay: 240ms;">
                <div class="h-[300px] flex items-center pt-4">
                    <canvas id="dashboard-total-commission-line"></canvas>
                </div>
            </x-dashboard.chart-card>
        </section>

        @if ($canViewMonthlyPerformance)
            <!-- Monthly Performance Bar Chart -->
            <section name="Monthly Performance" class="crm-anim-fade-up" style="--crm-anim-delay: 260ms;">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <x-dashboard.chart-card class="crm-dash-chart-panel">
                        <x-slot:header>
                            <div class="flex items-center relative h-8">
                                <!-- Left-aligned button -->
                                <a class="mr-3 rounded-lg border border-slate-300 bg-blue-500 px-3 py-2 text-[8px] font-semibold uppercase text-white shadow-sm transition hover:bg-white hover:text-slate-700"
                                    href="{{ route('dashboard.salespeople', ['tab' => 'salesperson']) }}">
                                    View All
                                </a>

                                <!-- Centered heading -->
                                <h3 class="absolute left-1/2 -translate-x-1/2">Salesperson</h3>
                            </div>
                        </x-slot:header>
                        <div class="h-[360px] max-w-[500px] m-auto"><canvas id="salespersonPerformanceChart"></canvas>
                        </div>
                    </x-dashboard.chart-card>
                    <x-dashboard.chart-card title="Leader" class="crm-dash-chart-panel">
                        <x-slot:header>
                            <div class="flex items-center relative h-8">
                                <!-- Left-aligned button -->
                                <a class="mr-3 rounded-lg border border-slate-300 bg-blue-500 px-3 py-2 text-[8px] font-semibold uppercase text-white shadow-sm transition hover:bg-white hover:text-slate-700"
                                    href="{{ route('dashboard.salespeople', ['tab' => 'leader']) }}">
                                    View All
                                </a>

                                <!-- Centered heading -->
                                <h3 class="absolute left-1/2 -translate-x-1/2">Leader</h3>
                            </div>
                        </x-slot:header>
                        <div class="h-[360px] max-w-[500px] m-auto"><canvas id="leaderPerformanceChart"></canvas></div>
                    </x-dashboard.chart-card>
                </div>
            </section>
        @endif

        <!-- Overview Dashboard -->
        <section name="Overview Dashboard">
            @include('dashboard.partials.dashboard-overview')
        </section>
    </div>

    @include('dashboard.partials.pipeline-stage-modal')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite('resources/js/dashboard.js')
</x-app-layout>
