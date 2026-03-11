<div x-show="dealFormOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    @click.self="dealFormOpen = false">
    <div class="crm-modal-panel">
        <div class="crm-modal-header">
            <h4 class="crm-modal-title" x-text="dealFormMode === 'edit' ? 'Edit Deal' : 'Create Deal'">Deal Form</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="dealFormOpen = false">X</button>
        </div>
        {{-- Unified deal form: create + edit are handled by mode, action, and _method --}}
        <form method="POST"
            :action="dealFormMode === 'edit' ? ('{{ url('deals') }}/' + (dealForm?.id ?? '')) : '{{ route('deals.store') }}'"
            data-preserve-list-state>
            @csrf
            {{-- Spoof HTTP method for edit mode; keep POST for create mode --}}
            <input type="hidden" name="_method" :value="dealFormMode === 'edit' ? 'PUT' : 'POST'">
            <div class="crm-modal-grid">
                {{-- Core deal fields --}}
                <div><x-input-label for="pipeline" :value="__('Pipeline Stage')" />
                    <select id="pipeline" :disabled="dealFormMode === 'edit' && dealForm?.pipeline_locked"
                        :required="!(dealFormMode === 'edit' && dealForm?.pipeline_locked)"
                        :name="dealFormMode === 'edit' && dealForm?.pipeline_locked ? null : 'pipeline'"
                        x-model="dealForm.pipeline" @change="toggleBookingAndSpa()" class="crm-form-select">
                        @foreach ($pipelines as $pipeline)
                            <option value="{{ $pipeline->value }}">{{ $pipeline->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="deal_salesperson_id" :value="__('Assign To')" />
                    <select id="deal_salesperson_id" name="salesperson_id" x-model="dealForm.salesperson_id"
                        :disabled="!canEditSalesperson" data-searchable-name="true"
                        data-search-placeholder="Search salesperson..." class="crm-form-select">
                        <option value="">Select a salesperson</option>
                        @foreach ($salespersons as $salesperson)
                            <option value="{{ $salesperson->id }}">{{ $salesperson->name }}</option>
                        @endforeach
                    </select>
                    <template x-if="!canEditSalesperson">
                        <input type="hidden" name="salesperson_id" :value="dealForm.salesperson_id">
                    </template>
                </div>
                <div><x-input-label for="lead_id" :value="__('Linked Lead')" />
                    <select id="lead_id" name="lead_id" x-model="dealForm.lead_id" required
                        data-searchable-name="true" data-search-placeholder="Search lead..." class="crm-form-select">
                        <option value="">Select a lead</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}">
                                {{ $lead->name }}{{ $lead->email ? ' - ' . $lead->email : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div><x-input-label for="project_name" :value="__('Project Name')" /><x-text-input id="project_name"
                        class="crm-form-text" type="text" name="project_name" x-model="dealForm.project_name"
                        required /></div>
                <div><x-input-label for="developer" :value="__('Developer')" /><x-text-input id="developer"
                        class="crm-form-text" type="text" name="developer" x-model="dealForm.developer" /></div>
                <div><x-input-label for="unit_number" :value="__('Unit Number')" /><x-text-input id="unit_number"
                        class="crm-form-text" type="text" name="unit_number" x-model="dealForm.unit_number" /></div>
                <div><x-input-label for="selling_price" :value="__('Selling Price')" /><x-text-input id="selling_price"
                        class="crm-form-text" type="number" step="0.01" name="selling_price"
                        x-model="dealForm.selling_price" required @input="recalc()" /></div>
                <div><x-input-label for="commission_percentage" :value="__('Commission %')" /><x-text-input
                        id="commission_percentage" class="crm-form-text" type="number" step="0.01"
                        name="commission_percentage" x-model="dealForm.commission_percentage" required
                        @input="recalc()" /></div>
                <div><x-input-label for="commission_amount" :value="__('Commission Amount')" /><x-text-input id="commission_amount"
                        class="crm-form-text crm-form-text-readonly" type="number" step="0.01"
                        name="commission_amount" x-model="dealForm.commission_amount" readonly /></div>
                {{-- Stage-dependent fields (Booking / SPA Signed) --}}
                <div id="booking_fee_group"><x-input-label for="booking_fee" :value="__('Booking Fee')" /><x-text-input
                        id="booking_fee" class="crm-form-text" type="number" step="0.01" name="booking_fee"
                        x-model="dealForm.booking_fee" /></div>
                <div id="spa_date_group"><x-input-label for="spa_date" :value="__('SPA Date')" /><x-text-input id="spa_date"
                        class="crm-form-text" type="date" name="spa_date" x-model="dealForm.spa_date" /></div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" @click="dealFormOpen = false" class="crm-btn-secondary">Cancel</button>
                <button type="submit" class="crm-btn-primary"
                    x-text="dealFormMode === 'edit' ? 'Save' : 'Create'">Save</button>
            </div>
        </form>
    </div>
</div>
