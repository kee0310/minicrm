<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="text-gray-900" x-data="{
                userFormOpen: false,
                userFormMode: 'create',
                userForm: {},
                searchTerm: @js(request('search', '')),
                roleFilter: @js(request('role', '')),
                ...tableListState({
                    endpoint: '{{ route('users.index') }}',
                    filters: { roleFilter: 'role' },
                }),
                emptyUserForm() {
                    return {
                        id: null,
                        name: '',
                        email: '',
                        role: '',
                        leader_id: '',
                        password: '',
                        password_confirmation: '',
                    };
                },
                openCreateUser() {
                    this.userFormMode = 'create';
                    this.userForm = this.emptyUserForm();
                    this.userFormOpen = true;
                },
                openEditUser(user) {
                    this.userFormMode = 'edit';
                    this.userForm = { ...this.emptyUserForm(), ...user };
                    this.userFormOpen = true;
                },
            }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">{{ __('List of users') }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" placeholder="Search name or email..."
                            :request-value="request('search', '')" />
                        <button type="button" @click="openCreateUser()" class="crm-create-btn">
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
                @include('users.partials.user-form-modals', [
                    'roles' => $roles,
                    'leaders' => $leaders,
                ])

            </div>
        </x-card>
    </div>
</x-app-layout>
