@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.legal.edit')" x-cloak
        x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.legal.edit')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title" x-text="legalForm?.has_record ? 'Edit Legal Case' : 'Add Legal Case'">Edit Legal
                    Case</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.legal.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ route('legals.index') }}/' + (legalForm?.deal_id ?? '')"
                data-preserve-list-state>
                @method('PUT')
                @csrf
                <div class="crm-modal-grid">
                    <div>
                        <label class="crm-form-label">Lawyer Firm</label>
                        <input type="text" name="lawyer_firm" x-model="legalForm.lawyer_firm"
                            class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">SPA Date</label>
                        <input type="date" name="spa_date" x-model="legalForm.spa_date"
                            class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Loan Agreement Date</label>
                        <input type="date" name="loan_agreement_date" x-model="legalForm.loan_agreement_date"
                            class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Completion Date</label>
                        <input type="date" name="completion_date" x-model="legalForm.completion_date"
                            class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Status</label>
                        <select name="status" x-model="legalForm.status" class="crm-form-select">
                            <option value="">-</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="crm-form-label">Assign To</label>
                        <select name="assign_to" x-model="legalForm.legal_officer_id"
                            data-searchable-name="true" data-search-placeholder="Search legal officer..."
                            class="crm-form-select">
                            @foreach ($legalOfficers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="stamp_duty" :value="legalForm?.stamp_duty ? 1 : 0" />
                            <input type="checkbox" x-model="legalForm.stamp_duty"
                                class="rounded border-gray-300 text-indigo-600" />
                            Stamp Duty
                        </label>
                    </div>
                </div>

                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.legal.edit')" class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif


