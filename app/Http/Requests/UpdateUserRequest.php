<?php

namespace App\Http\Requests;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
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
    $leaderIds = User::role([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])->pluck('id')->toArray();

    return [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique("users")->ignore($this->user)],
      'role' => ['required', 'string', Rule::exists('roles', 'name')],
      'leader_id' => [
        Rule::requiredIf(fn() => $this->input('role') === RoleEnum::SALESPERSON->value),
        'nullable',
        'integer',
        Rule::in($leaderIds),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'leader_id.required' => 'Leader is required when role is Salesperson.',
      'leader_id.in' => 'Selected leader must have Leader or Admin role.',
    ];
  }
}
