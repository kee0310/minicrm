<?php

namespace App\Http\Requests;

use App\Enums\LeadStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\RoleEnum;
use App\Models\User;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('leads', 'email'),
                Rule::unique('clients', 'email'),
            ],
            'phone' => 'required|string|max:20',
            'source' => 'required|string|max:255',
            'salesperson_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'leader_id' => function () {
                $leaderIds = User::role([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])->pluck('id')->toArray();
                return ['nullable', 'integer', Rule::in($leaderIds)];
            },
            'status' => ['required', 'string', Rule::in(LeadStatusEnum::values())],
            'age' => ['nullable', 'integer', 'min:1', 'required_if:status,' . LeadStatusEnum::DEAL->value],
            'ic_passport' => ['nullable', 'string', 'max:255', 'required_if:status,' . LeadStatusEnum::DEAL->value],
            'occupation' => ['nullable', 'string', 'max:255', 'required_if:status,' . LeadStatusEnum::DEAL->value],
            'company' => ['nullable', 'string', 'max:255', 'required_if:status,' . LeadStatusEnum::DEAL->value],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'required_if:status,' . LeadStatusEnum::DEAL->value],
        ];
    }

    public function messages(): array
    {
        return [
            'salesperson_id.exists' => 'Selected user does not exist.',
            'leader_id.exists' => 'Selected user does not exist.',
            'leader_id.in' => 'Selected user does not exist.',
            'email.unique' => 'This email already exists.',
        ];
    }
}
