<div class="crm-table-wrap">
    <table class="crm-table" data-sortable-table="true">
        <thead>
            <tr>
                <th class="text-right"></th>
                <th data-sort-index="1"><span class="crm-sort-btn">Salesperson Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="2"><span class="crm-sort-btn">Salesperson
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="3" data-sort-type="number"><span class="crm-sort-btn">Total (RM)
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="4" data-sort-type="number"><span class="crm-sort-btn">Paid (RM)
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="5" data-sort-type="number"><span class="crm-sort-btn">Remaining (RM)
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="6"><span class="crm-sort-btn">Payment Status
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="7" data-sort-type="date"><span class="crm-sort-btn">Deal Completed Date
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="8" data-sort-type="date"><span class="crm-sort-btn">Commission Paid Date
                        <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($commissions as $commission)
                @php
                    $deal = $commission->deal;
                    $total = (float) ($deal?->commission_amount ?? 0);
                    $paid = (float) ($commission?->paid ?? 0);
                    $remaining = max($total - $paid, 0);
                    $paymentStatus = $commission?->payment_status ?? 'Unpaid';
                    $statusClass =
                        $paymentStatus === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
                    $commissionPayload = [
                        'commission_id' => $commission->id,
                        'deal_id' => $deal->id,
                        'deal_code' => $deal->deal_id,
                        'project_name' => $deal->project_name,
                        'salesperson_name' => $deal->salesperson?->name,
                        'total' => $total,
                        'paid' => $paid,
                        'payment_status' => $paymentStatus,
                    ];
                @endphp
                <tr>
                    <td class="text-right">
                        <button type="button" data-commission='@json($commissionPayload)'
                            @click="commissionForm = JSON.parse($el.dataset.commission); openModal('commission.edit')"
                            class="crm-action-btn">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal?->deal_id ?? '')) }}">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal?->id ?? 'null' }}, 'commission.detail')">
                            {{ $deal?->deal_id ?? '-' }}
                        </button>:<br>
                        {{ $deal?->project_name ?? '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal?->salesperson?->name ?? '')) }}">
                        {{ $deal?->salesperson?->name ?? '-' }}</td>
                    <td data-sort-value="{{ $total }}">{{ number_format($total, 2) }}</td>
                    <td data-sort-value="{{ $paid }}">{{ number_format($paid, 2) }}</td>
                    <td data-sort-value="{{ $remaining }}">{{ number_format($remaining, 2) }}</td>
                    <td data-sort-value="{{ strtolower((string) $paymentStatus) }}">
                        <span
                            class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                            {{ $paymentStatus }}
                        </span>
                    </td>
                    <td data-sort-value="{{ $commission?->deal_completed_date ? \Illuminate\Support\Carbon::parse($commission->deal_completed_date)->format('Y-m-d') : '' }}"
                        class="crm-table-date">
                        {{ $commission?->deal_completed_date ? \Illuminate\Support\Carbon::parse($commission->deal_completed_date)->format('Y-m-d') : '-' }}
                    </td>
                    <td data-sort-value="{{ $commission?->deal_commission_paid_date ? \Illuminate\Support\Carbon::parse($commission->deal_commission_paid_date)->format('Y-m-d') : '' }}"
                        class="crm-table-date">
                        {{ $commission?->deal_commission_paid_date ? \Illuminate\Support\Carbon::parse($commission->deal_commission_paid_date)->format('Y-m-d') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="crm-table-empty">No commission records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $commissions->onEachSide(1)->links() }}
</div>
