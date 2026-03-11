@php
    $sortStart = $canManageLoanRecords ? 1 : 0;
    $isLoanOfficer = auth()->user()?->hasRole(\App\Enums\RoleEnum::LOAN_OFFICER->value) ?? false;
@endphp
<div class="crm-table-wrap">
    <table class="crm-table crm-table-center" data-sortable-table="true">
        <thead>
            <tr>
                @if ($canManageLoanRecords)
                    <th></th>
                @endif
                <th data-sort-index="{{ $sortStart }}"><span class="crm-sort-btn">Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 1 }}"><span class="crm-sort-btn">Client
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Loan Officer
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}"><span class="crm-sort-btn">Bank
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}"><span class="crm-sort-btn">Banker Contact
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 5 }}" class="w-[10%]" data-sort-type="date"><span
                        class="crm-sort-btn">Submission Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 6 }}" class="w-[5%]" data-sort-type="number"><span
                        class="crm-sort-btn">Doc Score <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 7 }}" class="w-[5%]" data-sort-type="number"><span
                        class="crm-sort-btn">File Completeness (%)
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 8 }}" class="w-[5%]"><span class="crm-sort-btn">Approval Status
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 9 }}" class="w-[10%]" data-sort-type="date"><span
                        class="crm-sort-btn">Expected Approval <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        @php
            $submissionRows = collect();
            foreach ($deals as $deal) {
                if ($deal->bankSubmissions->isEmpty()) {
                    $submissionRows->push([
                        'deal' => $deal,
                        'submission' => null,
                    ]);
                    continue;
                }

                foreach ($deal->bankSubmissions->sortByDesc('updated_at') as $submission) {
                    $submissionRows->push([
                        'deal' => $deal,
                        'submission' => $submission,
                    ]);
                }
            }
        @endphp
        <tbody>
            @forelse($submissionRows as $row)
                @php
                    $deal = $row['deal'];
                    $submission = $row['submission'];
                    $hasSubmission = !is_null($submission);
                    $submittedDateValue = optional($submission?->submission_date)->format('Y-m-d');
                    $isRejectedSubmission = $hasSubmission && $submission?->approval_status === 'Rejected';
                    $latestSubmission = $deal->bankSubmissions->sortByDesc('updated_at')->first();
                    $isLatestSubmission =
                        $hasSubmission &&
                        $latestSubmission &&
                        (string) $latestSubmission->loan_id === (string) $submission->loan_id;
                    $hasReplacementCase = $isRejectedSubmission && !$isLatestSubmission;
                    $showCreateAction = !$hasSubmission || ($isRejectedSubmission && !$hasReplacementCase);
                    $showActionButton = !$hasReplacementCase;
                    $hideEditForApproved =
                        $isLoanOfficer && $hasSubmission && $submission?->approval_status === 'Approved';
                    $createPayload = [
                        'mode' => 'create',
                        'deal_id' => (string) $deal->id,
                        'loan_id' => '',
                        'bank_name' => '',
                        'banker_contact' => '',
                        'submission_date' => '',
                        'document_completeness_score' => '',
                        'approval_status' => 'Prepared',
                        'expected_approval_date' => '',
                        'file_completeness_percentage' => '',
                    ];

                    $submissionPayload = $hasSubmission
                        ? [
                            'mode' => 'edit',
                            'deal_id' => $deal->id,
                            'loan_id' => $submission->loan_id,
                            'bank_name' => $submission->bank_name,
                            'banker_contact' => $submission->banker_contact,
                            'submission_date' => $submittedDateValue,
                            'document_completeness_score' => $submission->document_completeness_score,
                            'approval_status' => $submission->approval_status,
                            'expected_approval_date' => optional($submission->expected_approval_date)->format('Y-m-d'),
                            'file_completeness_percentage' => $submission->file_completeness_percentage,
                        ]
                        : null;

                    $statusClass = match ($submission?->approval_status) {
                        'Prepared' => 'bg-gray-200 text-gray-700',
                        'Submitted' => 'bg-blue-100 text-blue-700',
                        'In Review' => 'bg-amber-100 text-amber-700',
                        'Approved' => 'bg-green-100 text-green-700',
                        'Rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-200 text-gray-600',
                    };
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            @if ($showActionButton && $showCreateAction)
                                <button type="button" data-submission='@json($createPayload)'
                                    @click="bankForm = JSON.parse($el.dataset.submission); openModal('loan.bank.form')"
                                    class="{{ $isRejectedSubmission ? 'crm-action-btn-danger' : 'crm-action-btn' }}"
                                    title="{{ $isRejectedSubmission ? 'Create new case' : 'Create' }}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            @elseif ($showActionButton && !$hideEditForApproved)
                                <button type="button" data-submission='@json($submissionPayload)'
                                    @click="bankForm = JSON.parse($el.dataset.submission); openModal('loan.bank.form')"
                                    class="crm-action-btn" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            @endif
                        </td>
                    @endif
                    <td data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'loan.bank.detail'{{ $hasSubmission ? ', ' . \Illuminate\Support\Js::from($submission->loan_id) : '' }})">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->loanOfficer?->name ?? '')) }}">
                        {{ $deal->loanOfficer?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($submission?->bank_name ?? '')) }}">
                        <b>{{ $submission?->bank_name ?? '-' }}</b>
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($submission?->banker_contact ?? '')) }}">
                        {{ $submission?->banker_contact ?? '-' }}</td>
                    <td data-sort-value="{{ $submittedDateValue ?? '' }}" class="crm-table-date">
                        {{ $submittedDateValue ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($submission?->document_completeness_score ?? 0) }}">
                        {{ $submission?->document_completeness_score ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($submission?->file_completeness_percentage ?? 0) }}"
                        class=" {{ $submission?->file_completeness_percentage < 80 ? '!text-red-600' : '' }}">
                        {{ is_null($submission?->file_completeness_percentage) ? '-' : $submission->file_completeness_percentage . '%' }}
                    </td>
                    <td
                        data-sort-value="{{ strtolower((string) ($submission?->approval_status ?? 'No Submission')) }}">
                        @if ($hasSubmission)
                            <span
                                class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                                {{ $submission->approval_status }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td data-sort-value="{{ optional($submission?->expected_approval_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($submission?->expected_approval_date)->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '11' : '10' }}" class="crm-table-empty">No deals in
                        Booking/SPA
                        Signed/Loan stages.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $deals->onEachSide(1)->links() }}
</div>
