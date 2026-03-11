@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.disbursement.edit')" x-cloak
        x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.disbursement.edit')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title" x-text="editDeal?.has_record ? 'Edit Disbursement' : 'Add Disbursement'">Edit
                    Disbursement</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.disbursement.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/disbursement') }}/' + (editDeal?.deal_id ?? '')" data-preserve-list-state>
                @method('PUT')
                @csrf
                <input type="hidden" name="loan_id" :value="editDeal?.loan_id ?? ''">
                <div class="crm-modal-grid">
                    <div><label class="crm-form-label">First Disbursement
                            Date</label><input type="date" name="first_disbursement_date"
                            x-model="editDeal.first_disbursement_date" class="crm-form-input" /></div>
                    <div><label class="crm-form-label">Full Disbursement
                            Date</label><input type="date" name="full_disbursement_date"
                            x-model="editDeal.full_disbursement_date" class="crm-form-input" /></div>
                    <div><label class="crm-form-label">SPA Completion
                            Date</label><input type="date" name="spa_completion_date"
                            x-model="editDeal.spa_completion_date" class="crm-form-input" /></div>
                    <div><label class="crm-form-label">Client Notification
                            Date</label><input type="date" name="client_notification_date"
                            x-model="editDeal.client_notification_date" class="crm-form-input" /></div>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.disbursement.edit')"
                        class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif


