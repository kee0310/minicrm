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
                <th data-sort-index="{{ $sortStart }}" class="w-[15%]"><span class="crm-sort-btn">Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 1 }}" class="w-[12%]"><span class="crm-sort-btn">Client
                        <span data-sort-indicator></span></span>
                </th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Loan Officer
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}" data-sort-type="date"><span class="crm-sort-btn">First
                        Disbursement Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}" data-sort-type="date"><span class="crm-sort-btn">Full
                        Disbursement Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 5 }}" data-sort-type="date"><span class="crm-sort-btn">SPA
                        Completion Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 6 }}" data-sort-type="date"><span class="crm-sort-btn">Client
                        Notification Date <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($approvedSubmissions as $submission)
                @php
                    $deal = $submission->deal;
                    $hasDisbursement = !(
                        is_null($submission->first_disbursement_date) &&
                        is_null($submission->full_disbursement_date) &&
                        is_null($submission->spa_completion_date) &&
                        is_null($submission->client_notification_date)
                    );
                    $disbursementPayload = [
                        'deal_id' => $deal?->id,
                        'loan_id' => $submission->loan_id,
                        'has_record' => $hasDisbursement,
                        'first_disbursement_date' => optional($submission->first_disbursement_date)->format('Y-m-d'),
                        'full_disbursement_date' => optional($submission->full_disbursement_date)->format('Y-m-d'),
                        'spa_completion_date' => optional($submission->spa_completion_date)->format('Y-m-d'),
                        'client_notification_date' => optional($submission->client_notification_date)->format('Y-m-d'),
                    ];
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            <button type="button" data-disbursement='@json($disbursementPayload)'
                                @click="editDeal = JSON.parse($el.dataset.disbursement); openModal('loan.disbursement.edit')"
                                class="crm-action-btn">
                                <i class="fa-solid {{ $hasDisbursement ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                            </button>
                        </td>
                    @endif
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'loan.disbursement.detail', @js($submission->loan_id))">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->loanOfficer?->name ?? '')) }}">
                        {{ $deal->loanOfficer?->name ?? '-' }}</td>
                    <td data-sort-value="{{ optional($submission->first_disbursement_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($submission->first_disbursement_date)->format('d M Y') ?? '-' }}</td>
                    <td data-sort-value="{{ optional($submission->full_disbursement_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($submission->full_disbursement_date)->format('d M Y') ?? '-' }}</td>
                    <td data-sort-value="{{ optional($submission->spa_completion_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($submission->spa_completion_date)->format('d M Y') ?? '-' }}</td>
                    <td data-sort-value="{{ optional($submission->client_notification_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($submission->client_notification_date)->format('d M Y') ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '8' : '7' }}" class="crm-table-empty">
                        No approved loans found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $approvedSubmissions->onEachSide(1)->links() }}
</div>
