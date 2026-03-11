<div x-show="isModalOpen('commission.edit')" x-cloak
    x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="closeModal('commission.edit')">
    <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="crm-modal-panel">
        <div class="crm-modal-header">
            <h4 class="crm-modal-title">Edit Commission</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700"
                @click="closeModal('commission.edit')">X</button>
        </div>

        <form method="POST" :action="'{{ route('commissions.index') }}/' + (commissionForm?.commission_id ?? '')" data-preserve-list-state>
            @method('PUT')
            @csrf
            <div class="crm-modal-grid">
                <div>
                    <label class="crm-form-label">Project</label>
                    <input type="text"
                        :value="(commissionForm?.deal_code ?? '-') + ' - ' + (commissionForm?.project_name ?? '-')"
                        class="crm-form-input crm-form-input-readonly" readonly />
                </div>
                <div>
                    <label class="crm-form-label">Salesperson</label>
                    <input type="text" :value="commissionForm?.salesperson_name ?? '-'"
                        class="crm-form-input crm-form-input-readonly" readonly />
                </div>
                <div>
                    <label class="crm-form-label">Total</label>
                    <input type="text" :value="Number(commissionForm?.total ?? 0).toFixed(2)"
                        class="crm-form-input crm-form-input-readonly" readonly />
                </div>
                <div>
                    <label class="crm-form-label">Paid</label>
                    <input type="number" step="0.01" min="0" name="paid" x-model="commissionForm.paid"
                        class="crm-form-input" required />
                </div>
                <div class="md:col-span-2">
                    <label class="crm-form-label">Payment Status</label>
                    <select name="payment_status" x-model="commissionForm.payment_status"
                        class="crm-form-select" required>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="crm-modal-footer">
                <button type="button" @click="closeModal('commission.edit')" class="crm-btn-secondary">Cancel</button>
                <button type="submit" class="crm-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>


