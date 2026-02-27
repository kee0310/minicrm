<?php

namespace App\Http\Requests;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Deal|null $deal */
        $deal = $this->route('deal');
        $isPipelineLocked = $deal?->pipeline?->isLockedForManualEdit() ?? false;

        return [
            'lead_id' => [
                'required',
                'integer',
                Rule::exists('leads', 'id')->where(fn ($query) => $query->whereNotNull('leader_id')),
            ],
            'project_name' => ['required', 'string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'booking_fee' => ['nullable', 'numeric', 'min:0'],
            'spa_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => !$isPipelineLocked && $this->input('pipeline') === PipelineEnum::SPA_SIGNED->value),
            ],
            'deal_closing_date' => ['nullable', 'date'],
            'pipeline' => $isPipelineLocked
                ? ['prohibited']
                : ['required', 'string', Rule::in(PipelineEnum::creatableValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.exists' => 'Selected lead must have a leader assigned.',
        ];
    }
}
