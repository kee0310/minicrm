@if ($canManageLoanRecords)
    <div x-show="isModalOpen('loan.prequalification.edit')" x-cloak
        x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="closeModal('loan.prequalification.edit')">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="crm-modal-panel">
            <div class="crm-modal-header">
                <h4 class="crm-modal-title" x-text="editDeal?.has_record ? 'Edit Pre-Qualification' : 'Add Pre-Qualification'">
                    Edit
                    Pre-Qualification</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                    @click="closeModal('loan.prequalification.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/pre-qualification') }}/' + (editDeal?.deal_id ?? '')" data-preserve-list-state>
                @method('PUT')
                @csrf
                <div class="space-y-4">
                    @foreach ([1, 2, 3] as $recommendationIndex)
                        @include('loans.partials.pre-qualification-recommendation-fields', [
                            'index' => $recommendationIndex,
                            'bankOptions' => $bankOptions,
                        ])
                    @endforeach

                    <div>
                        <label class="crm-form-label">Pre-Qualification Date</label>
                        <input type="date" name="pre_qualification_date" x-model="editDeal.pre_qualification_date"
                            class="crm-form-input" />
                    </div>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" @click="closeModal('loan.prequalification.edit')"
                        class="crm-btn-secondary">Cancel</button>
                    <button type="submit" class="crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif


