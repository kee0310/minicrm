<?php

namespace App\Http\Requests;

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
            'lead_id' => ['required', 'integer', Rule::exists('leads', 'id')],
            'project_name' => ['required', 'string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'salesperson_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'leader_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'booking_fee' => ['nullable', 'numeric', 'min:0'],
            'spa_date' => ['nullable', 'date'],
            'deal_closing_date' => ['nullable', 'date'],
            'pipeline_id' => ['required', 'integer', Rule::exists('pipelines', 'id')],
        ];
    }
}
