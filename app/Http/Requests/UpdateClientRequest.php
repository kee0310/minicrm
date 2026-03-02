<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $client = $this->route('client');
        $salespersonIds = User::role([RoleEnum::SALESPERSON->value, RoleEnum::LEADER->value, RoleEnum::ADMIN->value])->pluck('id')->toArray();

        return [
            'salesperson_id' => ['required', 'integer', Rule::in($salespersonIds)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client?->id)],
            'phone' => ['required', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'ic_passport' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'status' => ['prohibited'],
            'completeness_rate' => ['prohibited'],
        ];
    }
}
