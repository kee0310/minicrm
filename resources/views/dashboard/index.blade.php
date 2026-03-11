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

    <div class="space-y-4 crm-dashboard-scroll">
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

    <x-modal name="dashboard-pipeline-stage-modal" :show="false" maxWidth="4xl" :simple="true" :lockScroll="false"
        :centered="true">
        <div class="border-b border-slate-200 px-5 py-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="pipeline-stage-modal-title" class="text-base font-semibold text-slate-900">Pipeline Stage
                    </h3>
                    <p id="pipeline-stage-modal-subtitle" class="mt-0.5 text-xs text-slate-500"></p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700"
                    x-on:click="$dispatch('close-modal', 'dashboard-pipeline-stage-modal')">
                    <span class="sr-only">Close</span>
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        </div>
        <div class="max-h-[70vh] overflow-y-auto mb-2">
            <div id="pipeline-stage-modal-loading" class="hidden py-8 text-center text-sm text-slate-500">
                Loading stage details...</div>
            <div id="pipeline-stage-modal-empty" class="hidden py-8 text-center text-sm text-slate-500">
                No records found for this stage.</div>
            <div id="pipeline-stage-modal-error" class="hidden py-8 text-center text-sm text-rose-600">
                Failed to load stage details.</div>
            <div id="pipeline-stage-modal-table-wrap" class="hidden overflow-x-auto px-5 py-4">
                <table class="min-w-full divide-y divide-slate-200 text-sm border border-slate-200">
                    <thead class="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-center">No.</th>
                            <th class="px-3 py-2">Deal ID</th>
                            <th class="px-3 py-2">Project Name</th>
                            <th class="px-3 py-2">Salesperson</th>
                            <th class="px-3 py-2">Leader</th>
                            <th class="px-3 py-2">Created Date</th>
                            <th id="pipeline-stage-modal-stage-date-header" class="px-3 py-2">Stage Date</th>
                        </tr>
                    </thead>
                    <tbody id="pipeline-stage-modal-tbody" class="divide-y divide-slate-200 bg-white text-slate-700">
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite('resources/js/dashboard.js')
</x-app-layout>
