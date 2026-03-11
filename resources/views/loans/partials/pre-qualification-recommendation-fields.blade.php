@props([
    'index',
    'bankOptions' => [],
])

<div class="rounded-md border border-gray-200 p-3">
    <h5 class="mb-3 text-sm font-semibold text-gray-800">Recommendation {{ $index }}</h5>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="crm-form-label">Recommended
                Bank</label>
            <select name="recommended_bank_{{ $index }}" x-model="editDeal.recommended_bank_{{ $index }}"
                class="crm-form-select">
                <option value="">-</option>
                @foreach ($bankOptions as $bank)
                    <option value="{{ $bank }}">{{ $bank }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="crm-form-label">Approval
                Probability (%)</label>
            <input type="number" name="approval_probability_{{ $index }}" min="0" max="100"
                x-model="editDeal.approval_probability_{{ $index }}" class="crm-form-input" />
        </div>
        <div>
            <label class="crm-form-label">Loan Margin
                (%)</label>
            <select name="loan_margin_{{ $index }}" x-model="editDeal.loan_margin_{{ $index }}"
                class="crm-form-select">
                <option value="">-</option>
                <option value="70">70%</option>
                <option value="80">80%</option>
                <option value="90">90%</option>
            </select>
        </div>
    </div>
</div>


