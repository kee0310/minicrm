@php
    $sortStart = $canManageLoanRecords ? 1 : 0;
    $isLegalOfficer = auth()->user()?->hasRole(\App\Enums\RoleEnum::LEGAL_OFFICER->value) ?? false;
@endphp
<div class="crm-table-wrap">
    <table class="crm-table crm-table-center" data-sortable-table="true">
        <thead>
            <tr>
                @if ($canManageLoanRecords)
                    <th></th>
                @endif
                <th data-sort-index="{{ $sortStart }}" class="col-left"><span class="crm-sort-btn">Project <span
                            data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 1 }}" class="col-left"><span class="crm-sort-btn">Client <span
                            data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Legal Officer <span
                            data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}"><span class="crm-sort-btn">Lawyer Firm <span
                            data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}" data-sort-type="date" class="whitespace-nowrap"><span
                        class="crm-sort-btn">SPA Date
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 5 }}" data-sort-type="date"><span class="crm-sort-btn">Loan
                        Agreement Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 6 }}" data-sort-type="date"><span class="crm-sort-btn">Completion
                        Date <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 7 }}" class="w-8%"><span class="crm-sort-btn">Stamp Duty <span
                            data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 8 }}"><span class="crm-sort-btn">Status <span
                            data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($deals as $deal)
                @php
                    $legal = $deal->legalCase;
                    $hasLegal = !is_null($legal);
                    $legalPayload = [
                        'deal_id' => $deal->id,
                        'project_name' => $deal->project_name,
                        'client_name' => $deal->client?->name,
                        'status' => $legal?->status ?? 'Drafting',
                        'lawyer_firm' => $legal?->lawyer_firm,
                        'spa_date' => optional($legal?->spa_date)->format('Y-m-d'),
                        'loan_agreement_date' => optional($legal?->loan_agreement_date)->format('Y-m-d'),
                        'completion_date' => optional($legal?->completion_date)->format('Y-m-d'),
                        'stamp_duty' => $legal?->stamp_duty,
                        'legal_officer_id' => $deal->legal_officer_id ?? auth()->id(),
                        'has_record' => $hasLegal,
                    ];

                    $statusClass = match ($legal?->status) {
                        'Drafting' => 'bg-gray-200 text-gray-700',
                        'Pending Bank' => 'bg-blue-100 text-blue-700',
                        'Pending Customer Signature' => 'bg-amber-100 text-amber-700',
                        'Completed' => 'bg-green-100 text-green-700',
                        default => '',
                    };
                    $hideCompletedEditForLegalOfficer = $isLegalOfficer && ($legal?->status === 'Completed');
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            @if (!$hideCompletedEditForLegalOfficer)
                                <button type="button" data-legal='@json($legalPayload)'
                                    @click="legalForm = JSON.parse($el.dataset.legal); openModal('loan.legal.edit')"
                                    class="crm-action-btn">
                                    <i class="fa-solid {{ $hasLegal ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                                </button>
                            @endif
                        </td>
                    @endif
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'deal.legal.detail')">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name ?? '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->legalOfficer?->name ?? '')) }}">
                        {{ $deal->legalOfficer?->name ?? '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($legal?->lawyer_firm ?? '')) }}">
                        {{ $legal?->lawyer_firm ?? '-' }}
                    </td>
                    <td data-sort-value="{{ optional($legal?->spa_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date whitespace-nowrap">
                        {{ optional($legal?->spa_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td data-sort-value="{{ optional($legal?->loan_agreement_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date whitespace-nowrap">
                        {{ optional($legal?->loan_agreement_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td data-sort-value="{{ optional($legal?->completion_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date whitespace-nowrap">
                        {{ optional($legal?->completion_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td
                        data-sort-value="{{ is_null($legal?->stamp_duty) ? '-' : ($legal->stamp_duty ? 'yes' : 'no') }}">
                        @if (is_null($legal?->stamp_duty))
                            -
                        @elseif($legal->stamp_duty)
                            <i class="fa-solid fa-check text-green-600" aria-label="Stamp duty yes"></i>
                        @else
                            <i class="fa-solid fa-xmark text-red-600" aria-label="Stamp duty no"></i>
                        @endif
                    </td>
                    <td data-sort-value="{{ strtolower((string) $legal?->status) }}">
                        <span
                            class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                            {{ $legal?->status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '11' : '10' }}" class="crm-table-empty">
                        No loan approved deals found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $deals->onEachSide(1)->links() }}
</div>
