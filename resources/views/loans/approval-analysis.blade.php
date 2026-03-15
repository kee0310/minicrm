<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Approval Analysis</h2>
    </x-slot>
    <div class="grid grid-cols-3 gap-3 md:grid-cols-4 lg:grid-cols-7 mb-4">
        @foreach ($bankApprovalRates ?? [] as $bankRate)
            <article class="crm-kpi">
                <p class="crm-kpi-label">{{ $bankRate['bank'] ?? '-' }}</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">
                    {{ number_format((float) ($bankRate['approval_rate'] ?? 0), 2) }}%
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ (int) ($bankRate['approved_count'] ?? 0) }} / {{ (int) ($bankRate['submitted_count'] ?? 0) }}
                    approved
                </p>
            </article>
        @endforeach
    </div>

    <div class="space-y-6">
        <x-card>
            <div x-data="loanPageState({
                editDeal: null,
                searchTerm: @js(request('search', '')),
                completionFilter: @js(request('completion', '')),
                bankFilter: @js(request('bank', '')),
                ...tableListState({
                    endpoint: '{{ route('loans.approval-analysis') }}',
                    filters: { completionFilter: 'completion', bankFilter: 'bank' },
                }),
            })">

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
                            <x-filter-tab-button state-key="completionFilter" value="" label="All"
                                :request-value="request('completion', '')" all />
                            <x-filter-tab-button state-key="completionFilter" :value="\App\Enums\CompletionFilterEnum::NEW->value" label="New"
                                :request-value="request('completion', '')" />
                            <x-filter-tab-button state-key="completionFilter" :value="\App\Enums\CompletionFilterEnum::COMPLETED->value" label="Completed"
                                :request-value="request('completion', '')" />
                        </div>
                    </div>
                </div>


                <div id="live-table-container" @click="handleTableClick($event)">@include('loans.partials.approval-analysis-table', [
                    'approvedSubmissions' => $approvedSubmissions,
                    'canManageLoanRecords' => $canManageLoanRecords,
                ])</div>

                {{-- Loan detail modal --}}
                @include('deals.partials.deal-detail-modal', ['modalKey' => 'loan.approval.detail'])

                @include('loans.partials.approval-analysis-form-modal', [
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'bankOptions' => $bankOptions,
                ])
            </div>
        </x-card>
    </div>
</x-app-layout>
