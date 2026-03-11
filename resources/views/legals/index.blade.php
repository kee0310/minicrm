<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Legal</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <article class="crm-kpi">
                <p class="crm-kpi-label">New</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['new'] ?? 0) }}</p>
            </article>
            @foreach ($statusOptions as $status)
                @php $key = strtolower(str_replace(' ', '_', $status)); @endphp
                <article class="crm-kpi">
                    <p class="crm-kpi-label">{{ $status }}</p>
                    <p
                        class="mt-2 text-2xl font-semibold {{ strtolower($status) === 'completed' ? 'text-green-600' : 'text-slate-900' }}">
                        {{ number_format($summary[$key] ?? 0) }}</p>
                </article>
            @endforeach
        </section>

        <x-card>
            <div x-data="loanPageState({
                legalForm: null,
                searchTerm: @js(request('search', '')),
                statusFilter: @js(request('status', '')),
                ...tableListState({
                    endpoint: '{{ route('legals.index') }}',
                    filters: { statusFilter: 'status' },
                }),
            })">
                <div class="crm-filter-block">
                    <x-filter-search-row model="searchTerm" placeholder="Search project or client..."
                        :request-value="request('search', '')" />
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="statusFilter" value="" label="All"
                                :request-value="request('status', '')" all />
                            <x-filter-tab-button state-key="statusFilter" value="New" label="New"
                                :request-value="request('status', '')" />
                            @foreach ($statusOptions as $status)
                                <x-filter-tab-button state-key="statusFilter" :value="$status" :label="$status"
                                    :request-value="request('status', '')" />
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('legals.partials.legals-table', [
                    'deals' => $deals,
                    'canManageLoanRecords' => $canManageLoanRecords,
                ])</div>

                @include('deals.partials.deal-detail-modal', ['modalKey' => 'deal.legal.detail'])
                @include('legals.partials.legal-form-modal', [
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'statusOptions' => $statusOptions,
                    'legalOfficers' => $legalOfficers,
                ])
            </div>
        </x-card>
    </div>
</x-app-layout>
