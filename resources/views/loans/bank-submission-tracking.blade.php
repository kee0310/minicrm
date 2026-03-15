<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Bank Submission Tracking</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <article class="crm-kpi">
                <p class="crm-kpi-label">New</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['new'] ?? 0) }}</p>
            </article>
            @foreach ($statusOptions as $status)
                @php $key = strtolower(str_replace(' ', '_', $status)); @endphp
                <article class="crm-kpi">
                    <p class="crm-kpi-label">{{ $status }}</p>
                    <p
                        class="mt-2 text-2xl font-semibold {{ strtolower($status) === 'rejected' ? 'text-red-600' : (strtolower($status) === 'approved' ? 'text-green-600' : 'text-slate-900') }}">
                        {{ number_format($summary[$key] ?? 0) }}</p>
                </article>
            @endforeach
        </section>

        <x-card>
            <div x-data="loanPageState({
                bankForm: null,
                searchTerm: @js(request('search', '')),
                statusFilter: @js(request('status', '')),
                bankFilter: @js(request('bank', '')),
                ...tableListState({
                    endpoint: '{{ route('loans.bank-submission-tracking') }}',
                    filters: { statusFilter: 'status', bankFilter: 'bank' },
                }),
            })">
                {{-- Client-side search and submission status filtering --}}
                <div class="crm-filter-block">
                    <div class="grid grid-cols-1 sm:grid-cols-2 justify-between gap-3">
                        <x-filter-search-row model="searchTerm" placeholder="Search project, client or banker..."
                            :request-value="request('search', '')" />
                        <div class="grid grid-cols-2">
                            <div></div>
                            <select class="crm-form-input" x-model="bankFilter" @change="refreshList()">
                                <option value="" @selected(request('bank', '') === '')>All Banks</option>
                                @foreach ($bankOptions as $bank)
                                    <option value="{{ $bank }}" @selected(request('bank') === $bank)>{{ $bank }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="statusFilter" value="" label="All"
                                :request-value="request('status', '')" all />
                            <x-filter-tab-button state-key="statusFilter" value="No Submission" label="New"
                                :request-value="request('status', '')" />
                            @foreach ($statusOptions as $status)
                                <x-filter-tab-button state-key="statusFilter" :value="$status" :label="$status"
                                    :request-value="request('status', '')" />
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('loans.partials.bank-submission-tracking-table', [
                    'deals' => $deals,
                    'canManageLoanRecords' => $canManageLoanRecords,
                ])</div>

                {{-- Loan detail modal --}}
                @include('deals.partials.deal-detail-modal', ['modalKey' => 'loan.bank.detail'])
                @include('loans.partials.bank-submission-form-modal', [
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'bankOptions' => $bankOptions,
                    'statusOptions' => $statusOptions,
                ])
            </div>
        </x-card>
    </div>
</x-app-layout>
