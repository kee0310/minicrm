<div class="crm-table-wrap">
    <table class="crm-table" data-sortable-table="true">
        <thead>
            <tr>
                <th class="w-[50px]"><span class="crm-sort-btn pointer-events-none">No.</span></th>
                <th data-sort-index="1"><span class="crm-sort-btn">Name
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="2"><span class="crm-sort-btn">Email
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="3"><span class="crm-sort-btn">Phone
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="4" data-sort-type="number"><span class="crm-sort-btn">Age
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="5"><span class="crm-sort-btn">Occupation
                        <span data-sort-indicator></span></span></th>
                <th data-sort-index="6"><span class="crm-sort-btn">Company
                        <span data-sort-indicator></span></span></th>
            </tr>
        </thead>
        <tbody class="whitespace-nowrap">
            @forelse ($clients as $index => $client)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td data-sort-value="{{ strtolower((string) $client->name) }}" class="text-gray-900"
                        style="text-align: left; padding-left: 20px">
                        <a href="{{ route('clients.show', $client->id) }}" class="text-indigo-600 hover:underline">
                            {{ $client->name }}
                        </a>
                    </td>
                    <td data-sort-value="{{ strtolower((string) $client->email) }}">{{ $client->email }}</td>
                    <td data-sort-value="{{ strtolower((string) $client->phone) }}">{{ $client->phone }}</td>
                    <td data-sort-value="{{ $client->age ?? '' }}">{{ $client->age ?? '-' }}</td>
                    <td data-sort-value="{{ strtolower((string) $client->occupation) }}">
                        {{ $client->occupation ?? '-' }}
                    </td>
                    <td data-sort-value="{{ strtolower((string) $client->company) }}">
                        {{ $client->company ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="crm-table-empty">No clients found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $clients->onEachSide(1)->links() }}
</div>
