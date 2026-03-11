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
                <th data-sort-index="{{ $sortStart + 1 }}" class="col-left"><span class="crm-sort-btn">Client
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Loan Officer
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}"><span class="crm-sort-btn">Risk
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}" data-sort-type="number"><span class="crm-sort-btn">Existing
                        Loans <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 5 }}" data-sort-type="number"><span class="crm-sort-btn">Monthly
                        Commitments <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 6 }}" data-sort-type="number"><span class="crm-sort-btn">Credit
                        Card Limits <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 7 }}" data-sort-type="number"><span class="crm-sort-btn">Card
                        Utilization (%) <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 8 }}"><span class="crm-sort-btn">CCRIS
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 9 }}"><span class="crm-sort-btn">CTOS
                        <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($deals as $deal)
                @php
                    $client = $deal->client;
                    $financial = $deal->preQualification;
                    $hasFinancial = !is_null($financial);
                    $riskGrade = $financial?->riskGrade() ?? $financial?->risk_grade;
                    $riskClass =
                        $riskGrade === 'C'
                            ? 'bg-red-100 text-red-700'
                            : ($riskGrade === 'B'
                                ? 'bg-amber-100 text-amber-700'
                                : ($riskGrade === 'A'
                                    ? 'bg-green-100 text-green-700'
                                    : ''));
                    $clientPayload = [
                        'id' => $deal->id,
                        'deal_id' => $deal->deal_id,
                        'lead_id' => $deal->lead_id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'age' => $client->age,
                        'ic_passport' => $client->ic_passport,
                        'occupation' => $client->occupation,
                        'company' => $client->company,
                        'monthly_income' => $client->monthly_income,
                        'risk_grade' => $riskGrade,
                        'existing_loans' => $financial?->existing_loans,
                        'monthly_commitments' => $financial?->monthly_commitments,
                        'credit_card_limits' => $financial?->credit_card_limits,
                        'credit_card_utilization' => $financial?->credit_card_utilization,
                        'ccris' => $financial?->ccris,
                        'ctos' => $financial?->ctos,
                        'loan_officer_id' => $deal->loan_officer_id ?? ($currentLoanOfficerId ?? auth()->id()),
                        'has_record' => $hasFinancial,
                    ];
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            <button type="button" data-client='@json($clientPayload)'
                                @click="editClient = JSON.parse($el.dataset.client); openModal('loan.borrower.edit')"
                                class="crm-action-btn">
                                <i class="fa-solid {{ $hasFinancial ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                            </button>
                        </td>
                    @endif
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'loan.borrower.detail')">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->loan_officer_name ?? '')) }}">
                        {{ $deal->loan_officer_name ?? '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($riskGrade ?? '')) }}">
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold {{ $riskClass }}">{{ $riskGrade ?? '-' }}</span>
                    </td>
                    <td data-sort-value="{{ (float) ($financial?->existing_loans ?? 0) }}">
                        {{ $financial?->existing_loans ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($financial?->monthly_commitments ?? 0) }}">
                        {{ $financial?->monthly_commitments ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($financial?->credit_card_limits ?? 0) }}">
                        {{ $financial?->credit_card_limits ?? '-' }}</td>
                    <td data-sort-value="{{ (float) ($financial?->credit_card_utilization ?? 0) }}">
                        {{ $financial?->credit_card_utilization !== null ? $financial->credit_card_utilization . '%' : '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($financial?->ccris ?? '')) }}">
                        {{ $financial?->ccris ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($financial?->ctos ?? '')) }}">
                        {{ $financial?->ctos ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '11' : '10' }}" class="crm-table-empty">
                        No deals found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $deals->onEachSide(1)->links() }}
</div>
