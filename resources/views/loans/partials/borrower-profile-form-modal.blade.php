@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.borrower.edit')" x-cloak x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.borrower.edit')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title"
                    x-text="editClient?.has_record ? 'Edit Financial Profile' : 'Add Financial Profile'">
                    Edit Financial
                    Profile</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.borrower.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/borrower-profile') }}/' + (editClient?.id ?? '')"
                data-preserve-list-state>
                @method('PUT')
                @csrf
                <div class="crm-modal-grid">
                    <div class="md:col-span-2">
                        <label class="crm-form-label">Assign To</label>
                        <select name="assign_to" x-model="editClient.loan_officer_id" data-searchable-name="true"
                            data-search-placeholder="Search loan officer..." class="crm-form-select">
                            @foreach ($loanOfficers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="crm-form-label">Existing Loans</label>
                        <input type="number" step="0.01" name="existing_loans" x-model="editClient.existing_loans"
                            class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Monthly Commitments</label>
                        <input type="number" step="0.01" name="monthly_commitments"
                            x-model="editClient.monthly_commitments" class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Credit Card Limits</label>
                        <input type="number" step="0.01" name="credit_card_limits"
                            x-model="editClient.credit_card_limits" class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">Credit Card Utilization (%)</label>
                        <input type="number" name="credit_card_utilization"
                            x-model="editClient.credit_card_utilization" class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">CCRIS</label>
                        <input type="text" name="ccris" x-model="editClient.ccris" class="crm-form-input" />
                    </div>
                    <div>
                        <label class="crm-form-label">CTOS</label>
                        <input type="text" name="ctos" x-model="editClient.ctos" class="crm-form-input" />
                    </div>
                </div>

                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.borrower.edit')"
                        class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif
