<x-app-layout>
    <x-slot name="header" class="bg-white">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Borrower Profile</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div x-data="loanPageState({
                editClient: null,
                searchTerm: @js(request('search', '')),
                completionFilter: @js(request('completion', '')),
                ...tableListState({
                    endpoint: '{{ route('loans.borrower-profile') }}',
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

                <div id="live-table-container" @click="handleTableClick($event)">@include('loans.partials.borrower-profile-table', [
                    'deals' => $deals,
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'currentLoanOfficerId' => $currentLoanOfficerId,
                ])</div>

                {{-- Loan detail modal --}}
                @include('deals.partials.deal-detail-modal', ['modalKey' => 'loan.borrower.detail'])

                @include('loans.partials.borrower-profile-form-modal', [
                    'canManageLoanRecords' => $canManageLoanRecords,
                    'loanOfficers' => $loanOfficers,
                ])
            </div>
        </x-card>
    </div>
</x-app-layout>
