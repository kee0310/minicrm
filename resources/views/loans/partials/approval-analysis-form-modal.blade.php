@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.approval.edit')" x-cloak
        x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.approval.edit')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title" x-text="editDeal?.has_record ? 'Edit Approval Analysis' : 'Add Approval Analysis'">
                    Edit Approval
                    Analysis</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.approval.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/approval-analysis') }}/' + (editDeal?.deal_id ?? '')" data-preserve-list-state>
                @csrf
                <input type="hidden" name="_method" :value="editDeal?.has_record ? 'PUT' : 'POST'">
                <input type="hidden" name="loan_id" :value="editDeal?.loan_id ?? ''">
                <div class="crm-modal-grid">
                    <div><label class="crm-form-label">Approved Bank</label><select
                            name="approved_bank" x-model="editDeal.approved_bank" class="crm-form-select"
                            required>
                            <option value="">-</option>
                            @foreach ($bankOptions as $bank)
                                <option value="{{ $bank }}">{{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="crm-form-label">Applied Amount</label><input
                            type="number" step="0.01" name="applied_amount" x-model="editDeal.applied_amount"
                            class="crm-form-input" required /></div>
                    <div><label class="crm-form-label">Approved Amount</label><input
                            type="number" step="0.01" name="approved_amount" x-model="editDeal.approved_amount"
                            class="crm-form-input" required /></div>
                    <div><label class="crm-form-label">Interest Rate</label><input
                            type="number" step="0.01" name="interest_rate" x-model="editDeal.interest_rate"
                            class="crm-form-input" required /></div>
                    <div><label class="crm-form-label">Lock-in Period</label><input
                            type="text" name="lock_in_period" x-model="editDeal.lock_in_period"
                            class="crm-form-input" required />
                    </div>
                    <div><label class="crm-form-label">MRTA / MLTA</label><input
                            type="text" name="mrta_mlta" x-model="editDeal.mrta_mlta"
                            class="crm-form-input" required /></div>
                    <div class="md:col-span-2"><label class="crm-form-label">Special
                            Conditions</label><input type="text" name="special_conditions"
                            x-model="editDeal.special_conditions" class="crm-form-input" /></div>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.approval.edit')"
                        class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif


