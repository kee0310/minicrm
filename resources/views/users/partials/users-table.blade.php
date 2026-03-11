@if (isset($users) && $users->count())
    <div class="crm-table-wrap">
        <table class="crm-table" data-sortable-table="true">
            <thead>
                <tr>
                    <th></th>
                    <th data-sort-index="1"><span class="crm-sort-btn">Name <span data-sort-indicator></span></span></th>
                    <th data-sort-index="2"><span class="crm-sort-btn">Email <span data-sort-indicator></span></span></th>
                    <th data-sort-index="3"><span class="crm-sort-btn">Role <span data-sort-indicator></span></span></th>
                    <th data-sort-index="4"><span class="crm-sort-btn">Leader <span data-sort-indicator></span></span></th>
                    <th data-sort-index="5" data-sort-type="date"><span class="crm-sort-btn">Created
                            <span data-sort-indicator></span></span></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="whitespace-nowrap">
                @foreach ($users as $user)
                    <tr>
                        <td>
                            @php
                                $roleNames = $user->roles->pluck('name')->values();
                                $userPayload = [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'role' => $roleNames->first(),
                                    'leader_id' => $user->leader_id,
                                ];
                            @endphp
                            <button type="button" class="crm-action-btn" data-user='@json($userPayload)'
                                @click="editUser = JSON.parse($el.dataset.user); editOpen = true">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                        <td data-sort-value="{{ strtolower((string) $user->name) }}" class="text-gray-900">
                            {{ $user->name }}</td>
                        <td data-sort-value="{{ strtolower((string) $user->email) }}">{{ $user->email }}</td>
                        <td data-sort-value="{{ strtolower((string) $roleNames->join(', ')) }}">
                            {{ $roleNames->join(', ') }}</td>
                        <td data-sort-value="{{ strtolower((string) ($user->leader_name ?? '')) }}">
                            {{ $user->leader_name ?? '-' }}</td>
                        <td data-sort-value="{{ optional($user->created_at)->format('Y-m-d') }}" class="crm-table-date">
                            {{ optional($user->created_at)->format('Y-m-d') }}</td>
                        <td>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                data-confirm="Confirm to delete user {{ $user->name }}?">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="crm-action-btn-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->onEachSide(1)->links() }}
    </div>
@else
    <div class="crm-table-empty-inline">{{ __('No users found.') }}</div>
@endif
