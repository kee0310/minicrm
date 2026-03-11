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
                <th data-sort-index="{{ $sortStart }}"><span class="crm-sort-btn">Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 1 }}"><span class="crm-sort-btn">Client
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 2 }}"><span class="crm-sort-btn">Loan Officer
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 3 }}"><span class="crm-sort-btn">Risk
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="{{ $sortStart + 4 }}" class="w-[15%]">
                    <span class="crm-sort-btn whitespace-nowrap">Bank 1
                        <span data-sort-indicator></span></span>
                </th>
                <th data-sort-index="{{ $sortStart + 5 }}" class="w-[15%]">
                    <span class="crm-sort-btn whitespace-nowrap">Bank 2
                        <span data-sort-indicator></span></span>
                </th>
                <th data-sort-index="{{ $sortStart + 6 }}" class="w-[15%]">
                    <span class="crm-sort-btn whitespace-nowrap">Bank 3
                        <span data-sort-indicator></span></span>
                </th>
                <th data-sort-index="{{ $sortStart + 7 }}" data-sort-type="date"><span
                        class="crm-sort-btn whitespace-nowrap">Qualificated
                        at <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($deals as $deal)
                @php
                    $pre = $deal->preQualification;
                    $riskGrade = $pre?->riskGrade() ?? $pre?->risk_grade;
                    $riskClass =
                        $riskGrade === 'C'
                            ? 'bg-red-100 text-red-700'
                            : ($riskGrade === 'B'
                                ? 'bg-amber-100 text-amber-700'
                                : ($riskGrade === 'A'
                                    ? 'bg-green-100 text-green-700'
                                    : ''));
                    $storedRecommendations = is_array($pre?->recommended_banks) ? $pre->recommended_banks : [];
                    $hasStructuredRecommendations =
                        !empty($storedRecommendations) &&
                        is_array($storedRecommendations[0] ?? null) &&
                        array_key_exists('bank', $storedRecommendations[0]);

                    if ($hasStructuredRecommendations) {
                        $recommendations = collect([0, 1, 2])
                            ->map(
                                fn($index) => [
                                    'bank' => $storedRecommendations[$index]['bank'] ?? null,
                                    'approval_probability' =>
                                        $storedRecommendations[$index]['approval_probability'] ?? null,
                                    'loan_margin' => $storedRecommendations[$index]['loan_margin'] ?? null,
                                ],
                            )
                            ->all();
                    } else {
                        $recommendations = collect([0, 1, 2])
                            ->map(
                                fn($index) => [
                                    'bank' => $storedRecommendations[$index] ?? null,
                                    'approval_probability' => null,
                                    'loan_margin' => null,
                                ],
                            )
                            ->all();
                    }
                    $hasPreQualificationData =
                        !is_null($pre?->pre_qualification_date) ||
                        collect($recommendations)->contains(function ($item) {
                            return !empty($item['bank']) ||
                                !is_null($item['approval_probability']) ||
                                !is_null($item['loan_margin']);
                        });
                    $prePayload = [
                        'deal_id' => $deal->id,
                        'has_record' => $hasPreQualificationData,
                        'deal_code' => $deal->deal_id,
                        'project_name' => $deal->project_name,
                        'client_name' => $deal->client?->name,
                        'pre_qualification_date' =>
                            optional($pre?->pre_qualification_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'recommended_bank_1' => $recommendations[0]['bank'] ?? null,
                        'recommended_bank_2' => $recommendations[1]['bank'] ?? null,
                        'recommended_bank_3' => $recommendations[2]['bank'] ?? null,
                        'approval_probability_1' => $recommendations[0]['approval_probability'] ?? null,
                        'approval_probability_2' => $recommendations[1]['approval_probability'] ?? null,
                        'approval_probability_3' => $recommendations[2]['approval_probability'] ?? null,
                        'loan_margin_1' => $recommendations[0]['loan_margin'] ?? null,
                        'loan_margin_2' => $recommendations[1]['loan_margin'] ?? null,
                        'loan_margin_3' => $recommendations[2]['loan_margin'] ?? null,
                    ];
                @endphp
                <tr>
                    @if ($canManageLoanRecords)
                        <td>
                            <button type="button" data-pre='@json($prePayload)'
                                @click="editDeal = JSON.parse($el.dataset.pre); openModal('loan.prequalification.edit')"
                                class="crm-action-btn">
                                <i class="fa-solid {{ $hasPreQualificationData ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                            </button>
                        </td>
                    @endif
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'loan.prequalification.detail')">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td class="col-left" data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                        {{ $deal->client?->name }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->loanOfficer?->name ?? '')) }}">
                        {{ $deal->loanOfficer?->name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($riskGrade ?? '')) }}">
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold {{ $riskClass }}">{{ $riskGrade ?? '-' }}</span>
                    </td>
                    @for ($i = 0; $i < 3; $i++)
                        <td
                            data-sort-value="{{ strtolower((string) (($recommendations[$i]['bank'] ?? '') . ' ' . ($recommendations[$i]['loan_margin'] ?? '') . ' ' . ($recommendations[$i]['approval_probability'] ?? ''))) }}">
                            @php $rec = $recommendations[$i] ?? null; @endphp

                            @if ($rec)
                                <div class="grid">
                                    @if (!empty($rec['bank']))
                                        <b>{{ $rec['bank'] }}</b>
                                    @else
                                        -
                                    @endif
                                    @if (isset($rec['loan_margin']))
                                        <em class="text-xs">
                                            Loan Margin: {{ $rec['loan_margin'] }}%
                                        </em>
                                    @endif
                                    @if (isset($rec['approval_probability']))
                                        <em class="text-xs">
                                            Approval Probability: {{ $rec['approval_probability'] }}%
                                        </em>
                                    @endif
                                </div>
                            @endif
                        </td>
                    @endfor
                    <td data-sort-value="{{ optional($pre?->pre_qualification_date)->format('Y-m-d') ?? '' }}"
                        class="crm-table-date">
                        {{ optional($pre?->pre_qualification_date)->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManageLoanRecords ? '9' : '8' }}" class="crm-table-empty">
                        No new deals found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $deals->onEachSide(1)->links() }}
</div>
