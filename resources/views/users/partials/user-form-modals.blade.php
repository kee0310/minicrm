<div x-show="userFormOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    @click.self="userFormOpen = false">
    <div class="crm-modal-panel">
        <div class="crm-modal-header">
            <h4 class="crm-modal-title" x-text="userFormMode === 'edit' ? 'Edit User' : 'Create User'">User Form</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="userFormOpen = false">X</button>
        </div>
        <form method="POST" x-data="{ showBox: false }"
            :action="userFormMode === 'edit' ? ('{{ url('users') }}/' + (userForm?.id ?? '')) : '{{ route('users.store') }}'"
            data-preserve-list-state
            @submit.prevent="
                if (userFormMode === 'create' && (userForm.password || '').length < 8) {
                    showBox = true;
                    return;
                }
                if (userFormMode === 'create' && userForm.password !== userForm.password_confirmation) {
                    showBox = true;
                    return;
                }
                $el.submit();
            ">
            @csrf
            <template x-if="userFormMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <div class="crm-modal-grid">
                <div><x-input-label for="user_name" :value="__('Name')" /><x-text-input id="user_name"
                        class="crm-form-text" type="text" name="name" x-model="userForm.name" required /></div>
                <div><x-input-label for="user_email" :value="__('Email')" /><x-text-input id="user_email"
                        class="crm-form-text" type="email" name="email" x-model="userForm.email" required /></div>
                <template x-if="userFormMode === 'create'">
                    <div>
                        <x-input-label for="user_password" :value="__('Password')" />
                        <x-text-input id="user_password" class="crm-form-text" type="password" name="password"
                            x-model="userForm.password" required />
                        <p class="mt-1 text-xs text-red-600" x-show="showBox && (userForm.password || '').length < 8"
                            x-cloak>
                            Password must be atleast 8 characters.
                        </p>
                    </div>
                </template>
                <template x-if="userFormMode === 'create'">
                    <div>
                        <x-input-label for="user_password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="user_password_confirmation" class="crm-form-text" type="password"
                            name="password_confirmation" x-model="userForm.password_confirmation" required />
                        <p class="mt-1 text-xs text-red-600"
                            x-show="showBox && userForm.password !== userForm.password_confirmation" x-cloak>
                            Password not match.
                        </p>
                    </div>
                </template>
                <div>
                    <x-input-label for="user_role" :value="__('Role')" />
                    <select id="user_role" name="role" x-model="userForm.role" required data-enhanced-select="true"
                        class="crm-form-select">
                        <option value="">Select a role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-red-600" x-show="showBox && !userForm.role" x-cloak>
                        Please select role.
                    </p>
                </div>
                <div x-show="userForm?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'" x-cloak>
                    <x-input-label for="user_leader_id" :value="__('Leader')" />
                    <select id="user_leader_id" name="leader_id" x-model="userForm.leader_id"
                        x-bind:required="userForm?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                        data-searchable-name="true" data-search-placeholder="Search leader..." class="crm-form-select">
                        <option value="">Select leader</option>
                        @foreach ($leaders as $leader)
                            <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-red-600" x-show="showBox && !userForm.leader_id" x-cloak>
                        Please select a leader.
                    </p>
                </div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" @click="userFormOpen = false" class="crm-btn-secondary">Cancel</button>
                <button type="submit" class="crm-btn-primary" @click="showBox = true"
                    x-text="userFormMode === 'edit' ? 'Save' : 'Create'">Save</button>
            </div>
        </form>
    </div>
</div>
