@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.bank.form')" x-cloak x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.bank.form')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title" x-text="bankForm?.mode === 'edit' ? 'Edit Bank Submission' : 'Create Case'">
                    Edit
                    Bank Submission</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.bank.form')">X</button>
            </div>
            <form method="POST" data-preserve-list-state
                :action="bankForm?.mode === 'edit' ?
                    '{{ url('/loans/bank-submission-tracking/submissions') }}/' + (bankForm?.loan_id ??
                        '') :
                    '{{ route('loans.bank-submission-tracking.store') }}'">
                @csrf
                <input type="hidden" name="_method" :value="bankForm?.mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="deal_id" :value="bankForm?.deal_id ?? ''">
                <div class="crm-modal-grid">
                    <div><label class="crm-form-label">Bank Name</label><select name="bank_name"
                            x-model="bankForm.bank_name" class="crm-form-select" required x-cloak>
                            <option value="">Select</option>
                            @foreach ($bankOptions as $bank)
                                <option value="{{ $bank }}">
                                    {{ $bank }}
                                </option>
                            @endforeach
                        </select></div>
                    <div><label class="crm-form-label">Banker Contact</label><input type="text" name="banker_contact"
                            x-model="bankForm.banker_contact" class="crm-form-input" required /></div>
                    <div><label class="crm-form-label">Submission Date</label><input type="date"
                            name="submission_date" x-model="bankForm.mode === 'edit' ? bankForm.submission_date : ''"
                            class="crm-form-input" /></div>
                    <div><label class="crm-form-label">Doc Score (1-5)</label><input type="number"
                            name="document_completeness_score" min="1" max="5"
                            x-model="bankForm.document_completeness_score" class="crm-form-input" required />
                    </div>
                    <div><label class="crm-form-label">Approval Status</label><select name="approval_status"
                            x-model="bankForm.approval_status" class="crm-form-select" required>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select></div>
                    <div><label class="crm-form-label">Expected Approval Date</label><input type="date"
                            name="expected_approval_date" x-model="bankForm.expected_approval_date"
                            class="crm-form-input" required /></div>
                    <div><label class="crm-form-label">File Completeness (%)</label><input type="number"
                            name="file_completeness_percentage" min="0" max="100"
                            x-model="bankForm.file_completeness_percentage" class="crm-form-input" required /></div>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.bank.form')"
                        class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary"
                        x-text="bankForm?.mode === 'edit' ? 'Save' : 'Create'">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif
