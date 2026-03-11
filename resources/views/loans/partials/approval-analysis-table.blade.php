@php
    $sortStart = $canManageLoanRecords ? 1 : 0;
@endphp
<div class="crm-table-wrap">
    <table class="crm-table crm-table-center" data-sortable-table="true">
        <thead>
            <tr>
                @if ($canManageLoanRecords)
                    <th></th>
                @endif
                <th data-sort-index="{{ $sortStart }}" class="w-[20%]"><span class="crm-sort-btn">Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 1 }}"><span class="crm-sort-btn">Client
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Loan Officer
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}"><span class="crm-sort-btn">Approved Bank
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}"><span class="crm-sort-btn">Banker
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 5 }}" data-sort-type="number"><span class="crm-sort-btn">Applied
                        Amount (RM)<span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 6 }}" data-sort-type="number"><span class="crm-sort-btn">Approved
                        Amount (RM)<span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 11 }}" data-sort-type="number"><span class="crm-sort-btn">Approval
                        Deviation (%) <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 7 }}" data-sort-type="number"><span class="crm-sort-btn">Interest
                        Rate <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 8 }}"><span class="crm-sort-btn">Lock-in Period
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 9 }}"><span class="crm-sort-btn">MRTA / MLTA
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 10 }}" class="w-[8%]"><span class="crm-sort-btn">Special
                        Conditions
                        <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($approvedSubmissions as $submission)
                @php
                    $deal = $submission->deal;
                    $hasRecord = !(
                        is_null($submission->applied_amount) &&
                        is_null($submission->approved_amount) &&
                        is_null($submission->interest_rate) &&
                        is_null($submission->lock_in_period) &&
                        is_null($submission->mrta_mlta) &&
                        is_null($submission->special_conditions)
                    );
                    $analysisPayload = [
                        'deal_id' => $deal?->id,
                        'loan_id' => $submission->loan_id,
                        'has_record' => $hasRecord,
                        'approved_bank' => $submission->approved_bank ?? $submission->bank_name,
                        'applied_amount' => $submission->applied_amount,
                        'approved_amount' => $submission->approved_amount,
                        'interest_rate' => $submission->interest_rate,
                        'lock_in_period' => $submission->lock_in_period,
                        'mrta_mlta' => $submission->mrta_mlta,
                        'special_conditions' => $submission->special_conditions,
                        'approval_deviation_percentage' => $submission->approval_deviation_percentage,
                    ];
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            <button type="button" data-analysis='@json($analysisPayload)'
                                @click="editDeal = JSON.parse($el.dataset.analysis); openModal('loan.approval.edit')"
                                class="crm-action-btn">
                                <i class="fa-solid {{ $hasRecord ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                            </button>
                        </td>
                    @endif
                    <td data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'loan.approval.detail', @js($submission->loan_id))">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->loanOfficer?->name ?? '')) }}">
                        {{ $deal->loanOfficer?->name ?? '-' }}</td>
                    <td
                        data-sort-value="{{ strtolower((string) ($submission->approved_bank ?? ($submission->bank_name ?? ''))) }}">
                        <b>{{ $submission->approved_bank ?? ($submission->bank_name ?? '-') }}</b>
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($submission->banker_contact ?? '')) }}">
                        {{ $submission->banker_contact ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($submission->applied_amount ?? 0) }}">
                        {{ number_format($submission->applied_amount, 2) }}</td>
                    <td data-sort-value="{{ (float) ($submission->approved_amount ?? 0) }}">
                        {{ number_format($submission->approved_amount, 2) }}</td>
                    <td data-sort-value="{{ (float) ($submission->approval_deviation_percentage ?? 0) }}">
                        {{ $submission->approval_deviation_percentage ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($submission->interest_rate ?? 0) }}">
                        {{ $submission->interest_rate ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($submission->lock_in_period ?? '')) }}">
                        {{ $submission->lock_in_period ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($submission->mrta_mlta ?? '')) }}">
                        {{ $submission->mrta_mlta ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($submission->special_conditions ?? '')) }}">
                        {{ $submission->special_conditions ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '13' : '12' }}" class="crm-table-empty">
                        No approved loans found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $approvedSubmissions->onEachSide(1)->links() }}
</div>
