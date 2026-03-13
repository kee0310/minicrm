<div x-show="leadFormOpen" x-cloak x-transition:enter="transition ease-in-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    @click.self="leadFormOpen = false">
    <div class="crm-modal-panel">
        <div class="crm-modal-header">
            <h4 class="crm-modal-title" x-text="leadFormMode === 'edit' ? 'Edit Lead' : 'Create Lead'">Lead Form</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="leadFormOpen = false">X</button>
        </div>
        {{-- Unified lead form: create + edit are handled by mode, action, and _method --}}
        <form method="POST"
            :action="leadFormMode === 'edit' ? ('{{ url('leads') }}/' + (leadForm?.id ?? '')) : '{{ route('leads.store') }}'"
            data-preserve-list-state>
            @csrf
            <input type="hidden" name="lead_id" :value="leadForm?.id ?? ''">
            {{-- Spoof HTTP method only for edit mode --}}
            <template x-if="leadFormMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <div class="crm-modal-grid">
                {{-- Common lead fields (always shown) --}}
                <div>
                    <x-input-label for="lead_salesperson_id" :value="__('Salesperson')" />
                    <input type="hidden" name="salesperson_id" :value="leadForm.salesperson_id">
                    <select id="lead_salesperson_id" :disabled="!canEditSalesperson" x-model="leadForm.salesperson_id"
                        data-searchable-name="true" data-search-placeholder="Search salesperson..."
                        class="crm-form-select" required>
                        <option value="">Select a user</option>
                        @foreach ($salespersons as $salesperson)
                            <option value="{{ $salesperson->id }}">{{ $salesperson->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="lead_source" :value="__('Source')" />
                    <select id="lead_source" name="source" x-model="leadForm.source" class="crm-form-select" required>
                        @foreach ($leadSourceOptions as $source)
                            <option value="{{ $source }}">{{ $source }}</option>
                        @endforeach
                    </select>
                </div>
                <div><x-input-label for="lead_name" :value="__('Name')" /><x-text-input id="lead_name"
                        class="crm-form-text" type="text" name="name" x-model="leadForm.name" required /></div>
                <div><x-input-label for="lead_email" :value="__('Email')" /><x-text-input id="lead_email"
                        class="crm-form-text" type="email" name="email" x-model="leadForm.email" required /></div>
                <div><x-input-label for="lead_phone" :value="__('Phone')" /><x-text-input id="lead_phone"
                        class="crm-form-text" type="text" name="phone" x-model="leadForm.phone" required /></div>
                <div>
                    <x-input-label for="lead_status" :value="__('Status')" />
                    <select id="lead_status" name="status" x-model="leadForm.status"
                        :disabled="leadFormMode === 'edit' && leadForm.status === 'Deal'"
                        @change="toggleDealFields()" class="crm-form-select" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                    <template x-if="leadFormMode === 'edit' && leadForm.status === 'Deal'">
                        <input type="hidden" name="status" :value="leadForm.status">
                    </template>
                </div>
            </div>

            {{-- Deal-only profile fields, toggled by status === Deal --}}
            <div id="lead_deal_fields" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div><x-input-label for="lead_age" :value="__('Age')" /><x-text-input id="lead_age"
                        class="crm-form-text" type="number" min="18" max="120" name="age"
                        x-model.number="leadForm.age" x-bind:required="leadForm.status === 'Deal'" />
                </div>
                <div><x-input-label for="lead_ic_passport" :value="__('IC/Passport')" /><x-text-input id="lead_ic_passport"
                        class="crm-form-text" type="text" name="ic_passport" x-model="leadForm.ic_passport"
                        x-bind:required="leadForm.status === 'Deal'" /></div>
                <div><x-input-label for="lead_occupation" :value="__('Occupation')" /><x-text-input id="lead_occupation"
                        class="crm-form-text" type="text" name="occupation" x-model="leadForm.occupation"
                        x-bind:required="leadForm.status === 'Deal'" /></div>
                <div><x-input-label for="lead_company" :value="__('Company')" /><x-text-input id="lead_company"
                        class="crm-form-text" type="text" name="company" x-model="leadForm.company"
                        x-bind:required="leadForm.status === 'Deal'" /></div>
                <div><x-input-label for="lead_monthly_income" :value="__('Monthly Income')" /><x-text-input
                        id="lead_monthly_income" class="crm-form-text" type="number" step="0.01" min="0"
                        name="monthly_income" x-model="leadForm.monthly_income"
                        x-bind:required="leadForm.status === 'Deal'" /></div>
                <div><x-input-label for="lead_working_years" :value="__('Working Years')" /><x-text-input
                        id="lead_working_years" class="crm-form-text" type="number" min="0"
                        name="working_years" x-model="leadForm.working_years"
                        x-bind:required="leadForm.status === 'Deal'" /></div>
                <div><x-input-label for="lead_fixed_income" :value="__('Fixed Income')" /><x-text-input id="lead_fixed_income"
                        class="crm-form-text" type="number" step="0.01" min="0" name="fixed_income"
                        x-model="leadForm.fixed_income" x-bind:required="leadForm.status === 'Deal'" /></div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" @click="leadFormOpen = false" class="crm-btn-secondary">Cancel</button>
                <button type="submit" class="crm-btn-primary"
                    x-text="leadFormMode === 'edit' ? 'Save' : 'Create'">Save</button>
            </div>
        </form>
    </div>
</div>
