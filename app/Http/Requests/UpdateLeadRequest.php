<?php

namespace App\Http\Requests;

use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $authUser */
        $authUser = $this->user();
        $salespersonQuery = User::query()->role(RoleEnum::SALESPERSON->value);
        $leaderQuery = User::query()->role(RoleEnum::LEADER->value);

        if ($authUser?->hasRole(RoleEnum::ADMIN->value)) {
            $salespersonIds = array_values(array_unique(array_merge(
                $salespersonQuery->pluck('id')->toArray(),
                $leaderQuery->pluck('id')->toArray(),
            )));
        } elseif ($authUser?->hasRole(RoleEnum::LEADER->value)) {
            $teamSalespersonIds = $salespersonQuery
                ->where('leader_id', $authUser->id)
                ->pluck('id')
                ->toArray();
            $salespersonIds = array_values(array_unique(array_merge(
                [(int) $authUser->id],
                $teamSalespersonIds,
            )));
        } elseif ($authUser?->hasRole(RoleEnum::SALESPERSON->value)) {
            $salespersonIds = [(int) $authUser->id];
        } else {
            $salespersonIds = [];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'source' => ['required', 'string', 'max:255'],
            'salesperson_id' => [
                'required',
                'integer',
                Rule::in($salespersonIds),
            ],
            'status' => ['required', 'string', Rule::in(LeadStatusEnum::values())],
            'age' => ['nullable', 'integer', 'min:1', 'max:120', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'ic_passport' => ['nullable', 'string', 'max:255', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'occupation' => ['nullable', 'string', 'max:255', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'company' => ['nullable', 'string', 'max:255', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'working_years' => ['nullable', 'integer', 'min:0', 'max:80', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'required_if:status,'.LeadStatusEnum::DEAL->value],
            'fixed_income' => ['nullable', 'numeric', 'min:0', 'required_if:status,'.LeadStatusEnum::DEAL->value],
        ];
    }

    public function messages(): array
    {
        return [
            'salesperson_id.exists' => 'Selected user does not exist.',
            'salesperson_id.in' => 'Selected salesperson does not have an allowed role.',
        ];
    }
}
