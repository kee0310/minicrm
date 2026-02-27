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
            'email' => 'required|email|unique:leads,email',
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
        ];
    }

    public function messages(): array
    {
        return [
            'salesperson_id.exists' => 'Selected user does not exist.',
            'leader_id.exists' => 'Selected user does not exist.',
            'leader_id.in' => 'Selected user does not exist.',
        ];
    }
}
