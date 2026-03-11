<div class="crm-table-wrap">
    <table class="crm-table" data-sortable-table="true">
        <thead>
            <tr>
                <th></th>
                <th data-sort-index="1"><span class="crm-sort-btn">Name
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="2"><span class="crm-sort-btn">Email
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="3"><span class="crm-sort-btn">Phone
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="4"><span class="crm-sort-btn">Source
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="5"><span class="crm-sort-btn">Salesperson
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="6"><span class="crm-sort-btn">Leader
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="7"><span class="crm-sort-btn">Status
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="8" data-sort-type="date"><span class="crm-sort-btn">Created Date
                        <span data-sort-indicator></span></span></th>
                <th></th>
            </tr>
        </thead>
        <tbody class="whitespace-nowrap">
            @forelse ($leads as $lead)
                <tr>
                    <td>
                        @php
                            $leadPayload = [
                                'id' => $lead->id,
                                'name' => $lead->name,
                                'email' => $lead->email,
                                'phone' => $lead->phone,
                                'source' => $lead->source,
                                'salesperson_id' => $lead->salesperson_id,
                                'status' => $lead->status?->value,
                                'age' => $lead->age,
                                'ic_passport' => $lead->ic_passport,
                                'occupation' => $lead->occupation,
                                'company' => $lead->company,
                                'working_years' => $lead->working_years,
                                'monthly_income' => $lead->monthly_income,
                                'fixed_income' => $lead->fixed_income,
                            ];
                        @endphp
                        <button type="button" class="crm-action-btn" data-lead='@json($leadPayload)'
                            @click="openEditLead(JSON.parse($el.dataset.lead))">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </td>
                    <td data-sort-value="{{ strtolower((string) $lead->name) }}" class="text-sm text-gray-900">
                        {{ $lead->name }}</td>
                    <td data-sort-value="{{ strtolower((string) $lead->email) }}">{{ $lead->email }}</td>
                    <td data-sort-value="{{ strtolower((string) $lead->phone) }}">{{ $lead->phone }}</td>
                    <td data-sort-value="{{ strtolower((string) $lead->source) }}">{{ $lead->source }}</td>
                    <td data-sort-value="{{ strtolower((string) ($lead->salesperson_name ?? '')) }}">
                        {{ $lead->salesperson_name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) ($lead->leader_name ?? '')) }}">
                        {{ $lead->leader_name ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) $lead->status?->value) }}">
                        <span class="{{ $lead->status->badge() }}">
                            {{ $lead->status->value }}
                        </span>
                    </td>
                    <td data-sort-value="{{ optional($lead->created_at)->format('Y-m-d') }}" class="crm-table-date">
                        {{ optional($lead->created_at)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td>
                        @if ($lead->status !== \App\Enums\LeadStatusEnum::DEAL)
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" class="inline"
                                data-preserve-list-state
                                data-confirm="Confirm to delete lead {{ $lead->name }}?">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="crm-action-btn-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="crm-table-empty">No leads found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $leads->onEachSide(1)->links() }}
</div>
