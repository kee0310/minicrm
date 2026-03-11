<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="text-gray-900" x-data="{
                createOpen: false,
                editOpen: false,
                createRole: '',
                editUser: null,
                searchTerm: @js(request('search', '')),
                roleFilter: @js(request('role', '')),
                ...tableListState({
                    endpoint: '{{ route('users.index') }}',
                    filters: { roleFilter: 'role' },
                }),
            }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">{{ __('List of users') }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" placeholder="Search name or email..."
                            :request-value="request('search', '')" />
                        <button type="button"
                            @click="createOpen = true; $nextTick(() => { recalc('create'); toggleBookingAndSpa('create'); })"
                            class="crm-create-btn">
                            {{ __('Create User') }}
                        </button>
                    </div>
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="roleFilter" value="" label="All" :request-value="request('role', '')"
                                all />
                            @foreach ($roles as $role)
                                <x-filter-tab-button state-key="roleFilter" :value="$role" :label="$role"
                                    :request-value="request('role', '')" />
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('users.partials.users-table', ['users' => $users])</div>

                <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    @click.self="createOpen = false">
                    <div class="crm-modal-panel">
                        <div class="crm-modal-header">
                            <h4 class="crm-modal-title">Create User</h4>
                            <button type="button" class="text-gray-500 hover:text-gray-700"
                                @click="createOpen = false">X</button>
                        </div>
                        <form method="POST" action="{{ route('users.store') }}">
                            @csrf
                            <div class="crm-modal-grid">
                                <div><x-input-label for="create_user_name" :value="__('Name')" /><x-text-input
                                        id="create_user_name" class="block mt-1 w-full" type="text" name="name"
                                        :value="old('name')" required /></div>
                                <div><x-input-label for="create_user_email" :value="__('Email')" /><x-text-input
                                        id="create_user_email" class="block mt-1 w-full" type="email" name="email"
                                        :value="old('email')" required /></div>
                                <div><x-input-label for="create_user_password" :value="__('Password')" /><x-text-input
                                        id="create_user_password" class="block mt-1 w-full" type="password"
                                        name="password" required />
                                </div>
                                <div><x-input-label for="create_user_password_confirmation"
                                        :value="__('Confirm Password')" /><x-text-input id="create_user_password_confirmation"
                                        class="block mt-1 w-full" type="password" name="password_confirmation"
                                        required /></div>
                                <div>
                                    <x-input-label for="create_user_role" :value="__('Role')" />
                                    <select id="create_user_role" name="role" x-model="createRole" required
                                        data-enhanced-select="true"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">Select a role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="createRole === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'" x-cloak>
                                    <x-input-label for="create_user_leader_id" :value="__('Leader')" />
                                    <select id="create_user_leader_id" name="leader_id"
                                        :required="createRole === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                                        data-searchable-name="true" data-search-placeholder="Search leader..."
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">Select leader</option>
                                        @foreach ($leaders as $leader)
                                            <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="crm-modal-footer">
                                <button type="button" @click="createOpen = false"
                                    class="crm-btn-secondary">Cancel</button>
                                <button type="submit" class="crm-btn-primary">Create</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    @click.self="editOpen = false">
                    <div class="crm-modal-panel">
                        <div class="crm-modal-header">
                            <h4 class="crm-modal-title">Edit User</h4>
                            <button type="button" class="text-gray-500 hover:text-gray-700"
                                @click="editOpen = false">X</button>
                        </div>
                        <form method="POST" :action="'{{ url('users') }}/' + (editUser?.id ?? '')" data-preserve-list-state>
                            @method('PUT')
                            @csrf
                            <div class="crm-modal-grid">
                                <div><x-input-label for="edit_user_name" :value="__('Name')" /><x-text-input
                                        id="edit_user_name" class="block mt-1 w-full" type="text" name="name"
                                        x-model="editUser.name" required /></div>
                                <div><x-input-label for="edit_user_email" :value="__('Email')" /><x-text-input
                                        id="edit_user_email" class="block mt-1 w-full" type="email" name="email"
                                        x-model="editUser.email" required /></div>
                                <div>
                                    <x-input-label for="edit_user_role" :value="__('Role')" />
                                    <select id="edit_user_role" name="role" x-model="editUser.role" required
                                        data-enhanced-select="true"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">Select a role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="editUser?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                                    x-cloak>
                                    <x-input-label for="edit_user_leader_id" :value="__('Leader')" />
                                    <select id="edit_user_leader_id" name="leader_id" x-model="editUser.leader_id"
                                        :required="editUser?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                                        data-searchable-name="true" data-search-placeholder="Search leader..."
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">Select leader</option>
                                        @foreach ($leaders as $leader)
                                            <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="crm-modal-footer">
                                <button type="button" @click="editOpen = false"
                                    class="crm-btn-secondary">Cancel</button>
                                <button type="submit" class="crm-btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </x-card>
    </div>
</x-app-layout>
