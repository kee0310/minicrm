<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight bg-white">Loans / Pre-Qualification</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div x-data="loanPageState({
                editDeal: null,
                searchTerm: @js(request('search', '')),
                completionFilter: @js(request('completion', '')),
                ...tableListState({
                    endpoint: '{{ route('loans.pre-qualification') }}',
                    filters: { completionFilter: 'completion' },
                }),
            })">
                <div class="crm-filter-block">
                    <x-filter-search-row model="searchTerm" placeholder="Search project or client..." :request-value="request('search', '')" />
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

                <div id="live-table-container" @click="handleTableClick($event)">@include('loans.partials.pre-qualification-table', [
                    'deals' => $deals,
                    'canManageLoanRecords' => $canManageLoanRecords,
                ])</div>

                {{-- Loan detail modal --}}
                @include('deals.partials.deal-detail-modal', [
                    'modalKey' => 'loan.prequalification.detail',
                ])

                @include('loans.partials.pre-qualification-form-modal', [
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'bankOptions' => $bankOptions,
                ])
            </div>
        </x-card>
    </div>
</x-app-layout>
