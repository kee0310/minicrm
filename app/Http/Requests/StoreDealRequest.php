<?php

namespace App\Http\Requests;

use App\Enums\PipelineEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'project_name' => ['required', 'string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'booking_fee' => ['nullable', 'numeric', 'min:0'],
            'spa_date' => ['nullable', 'date', 'required_if:pipeline,' . PipelineEnum::SPA_SIGNED->value],
            'deal_closing_date' => ['nullable', 'date'],
            'pipeline' => ['required', 'string', Rule::in(PipelineEnum::creatableValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Selected client does not exist.',
        ];
    }
}
