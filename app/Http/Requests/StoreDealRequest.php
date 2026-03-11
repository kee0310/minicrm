<?php

namespace App\Http\Requests;

use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
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
        $assignableLeadIds = $this->assignableLeadIds();
        $assignableSalespersonIds = $this->assignableSalespersonIds();

        return [
            'lead_id' => [
                'required',
                'integer',
                Rule::exists('leads', 'id'),
                Rule::in($assignableLeadIds),
            ],
            'project_name' => ['required', 'string', 'max:255'],
            'developer' => ['nullable', 'string', 'max:255'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'salesperson_id' => [
                'nullable',
                'integer',
                Rule::in($assignableSalespersonIds),
            ],
            'booking_fee' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => in_array($this->input('pipeline'), [PipelineEnum::BOOKING->value, PipelineEnum::SPA_SIGNED->value], true)),
            ],
            'spa_date' => ['nullable', 'date', 'required_if:pipeline,' . PipelineEnum::SPA_SIGNED->value],
            'deal_closing_date' => ['nullable', 'date'],
            'pipeline' => ['required', 'string', Rule::in(PipelineEnum::creatableValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.exists' => 'Selected lead does not exist.',
            'lead_id.in' => 'Selected lead is not accessible for your account.',
        ];
    }

    protected function assignableLeadIds(): array
    {
        $user = $this->user();
        $leads = Lead::query();

        if (! $user || $user->hasRole(RoleEnum::ADMIN->value)) {
            return $leads->pluck('id')->all();
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return $leads
                ->where(function ($query) use ($user) {
                    $query->where('leader_id', $user->id)
                        ->orWhere('salesperson_id', $user->id);
                })
                ->pluck('id')
                ->all();
        }

        return $leads
            ->where('salesperson_id', $user->id)
            ->pluck('id')
            ->all();
    }

    protected function assignableSalespersonIds(): array
    {
        $user = $this->user();
        $salespersons = User::query()->role([RoleEnum::SALESPERSON->value, RoleEnum::LEADER->value]);

        if (! $user || $user->hasRole(RoleEnum::ADMIN->value)) {
            return $salespersons->pluck('id')->all();
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return $salespersons
                ->where(function ($query) use ($user) {
                    $query->where('leader_id', $user->id)
                        ->orWhere('id', $user->id);
                })
                ->pluck('id')
                ->all();
        }

        return [$user->id];
    }
}
