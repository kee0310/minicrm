<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\RoleEnum;
use App\Models\User;

class StoreLeadsRequest extends FormRequest
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
            'assigned_to' => [
                'required',
                'string',
                'max:255',
                Rule::exists('users', 'name')->whereNull('deleted_at'),
            ],
            'leader' => function () {
                $leaderNames = User::role(RoleEnum::LEADER->value)->whereNull('deleted_at')->pluck('name')->toArray();
                return ['nullable', 'string', 'max:255', Rule::in($leaderNames)];
            },
            'status' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'Selected user does not exist.',
            'leader.exists' => 'Selected user does not exist.',
            'leader.in' => 'Selected user does not exist.',
        ];
    }
}
