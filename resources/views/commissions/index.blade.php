<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Commission</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-5">
            <article class="crm-kpi">
                <p class="crm-kpi-label">Commission Eligible</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['eligible'] ?? 0) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-kpi-label">Pending Approval</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700">
                    {{ number_format($summary['pending_approval'] ?? 0) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-kpi-label">Paid</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($summary['paid'] ?? 0) }}</p>
            </article>
        </section>

        <x-card>
            <div x-data="loanPageState({
                commissionForm: null,
                searchTerm: @js(request('search', '')),
                statusFilter: @js(request('status', '')),
                ...tableListState({
                    endpoint: '{{ route('commissions.index') }}',
                    filters: { statusFilter: 'status' },
                }),
            })">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Lists of Commissions</h3>
                </div>

                <div class="crm-filter-block">
                    <x-filter-search-row model="searchTerm" placeholder="Search deal, project or salesperson..."
                        :request-value="request('search', '')" />
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="statusFilter" value="" label="All"
                                :request-value="request('status', '')" all />
                            <x-filter-tab-button state-key="statusFilter" value="Paid" label="Paid"
                                :request-value="request('status', '')" />
                            <x-filter-tab-button state-key="statusFilter" value="Unpaid" label="Unpaid"
                                :request-value="request('status', '')" />
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('commissions.partials.commissions-table', ['commissions' => $commissions])</div>

                @include('deals.partials.deal-detail-modal', ['modalKey' => 'commission.detail'])
                @include('commissions.partials.commission-form-modal')
            </div>
        </x-card>
    </div>
</x-app-layout>


