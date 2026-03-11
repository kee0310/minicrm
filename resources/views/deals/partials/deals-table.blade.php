<div class="crm-table-wrap">
    <table class="crm-table crm-table-center" data-sortable-table="true">
        <thead>
            <tr>
                <th></th>
                <th data-sort-index="2" class="col-left"><span class="crm-sort-btn">Project
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="1" class="col-left"><span class="crm-sort-btn">Lead
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="3" data-sort-type="number"><span class="crm-sort-btn">Selling Price
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="4" data-sort-type="number"><span class="crm-sort-btn">Commission
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="5"><span class="crm-sort-btn">Stage
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="6"><span class="crm-sort-btn">Salesperson
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="7"><span class="crm-sort-btn">Leader
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="8" data-sort-type="date"><span class="crm-sort-btn">Created
                        <span data-sort-indicator></span></span></th>
                <th></th>
            </tr>
        </thead>
        <tbody class="whitespace-nowrap">
            @forelse ($deals as $deal)
                <tr>
                    <td>
                        @php
                            $dealPayload = [
                                'id' => $deal->id,
                                'lead_id' => $deal->lead_id,
                                'salesperson_id' => $deal->salesperson_id,
                                'project_name' => $deal->project_name,
                                'developer' => $deal->developer,
                                'unit_number' => $deal->unit_number,
                                'selling_price' => $deal->selling_price,
                                'commission_percentage' => $deal->commission_percentage,
                                'commission_amount' => $deal->commission_amount,
                                'booking_fee' => $deal->booking_fee,
                                'spa_date' => optional($deal->spa_date)->format('Y-m-d'),
                                'pipeline' => $deal->pipeline?->value,
                                'pipeline_locked' => $deal->pipeline?->isLockedForManualEdit() ?? false,
                            ];
                        @endphp
                        <button type="button" class="crm-action-btn" data-deal='@json($dealPayload)'
                            @click="openEditDeal(JSON.parse($el.dataset.deal))">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->deal_id ?? '')) }}" class="col-left">
                        <button type="button" class="truncate text-indigo-600 hover:underline"
                            @click="openLoanDetail({{ $deal->id }}, 'deal.detail')">
                            {{ $deal->deal_id }}:
                        </button><br>
                        {{ $deal->project_name }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->lead_name ?? '')) }}" class="col-left">
                        {{ $deal->lead_name ?? '-' }}
                    </td>
                    <td data-sort-value="{{ (float) $deal->selling_price }}">
                        {{ number_format($deal->selling_price, 2) }}</td>
                    <td data-sort-value="{{ (float) $deal->commission_amount }}">
                        {{ number_format($deal->commission_amount, 2) }}</td>
                    <td data-sort-value="{{ strtolower((string) $deal->pipeline?->value) }}">
                        <span class="{{ $deal->pipeline->badge() }}">
                            {{ $deal->pipeline->value }}
                        </span>
                    </td>
                    <td data-sort-value="{{ strtolower((string) ($deal->salesperson_name ?? '')) }}">
                        {{ $deal->salesperson_name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($deal->leader_name ?? '')) }}">
                        {{ $deal->leader_name ?? '-' }}</td>
                    <td data-sort-value="{{ optional($deal->created_at)->format('Y-m-d') }}" class="crm-table-date">
                        {{ optional($deal->created_at)->format('Y-m-d') }}</td>
                    <td>
                        <form method="POST" action="{{ route('deals.destroy', $deal) }}" class="inline"
                            data-confirm="Confirm to delete deal {{ $deal->project_name }}?">
                            @method('DELETE')
                            @csrf
                            <button type="submit" class="crm-action-btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="crm-table-empty">No deals found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $deals->onEachSide(1)->links() }}
</div>
