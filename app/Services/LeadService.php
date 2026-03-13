<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public function extractLeadPayload(array $validated): array
    {
        return Arr::only($validated, [
            'name',
            'email',
            'phone',
            'source',
            'salesperson_id',
            'status',
            'age',
            'ic_passport',
            'occupation',
            'company',
            'working_years',
            'monthly_income',
            'fixed_income',
        ]);
    }

    public function buildLeadPayload(array $validated): array
    {
        $payload = $this->extractLeadPayload($validated);
        $payload['leader_id'] = $this->resolveLeaderIdFromSalesperson((int) $payload['salesperson_id']);

        return $payload;
    }

    public function isLockedDealLead(Lead $lead): bool
    {
        return $lead->deals()->exists();
    }

    public function ensureLeadAccess(?User $user, Lead $lead): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $canAccess = Lead::query()
                ->visibleTo($user)
                ->where('id', $lead->id)
                ->exists();

            if (! $canAccess) {
                abort(403);
            }
        }
    }

    public function resolveLeaderIdFromSalesperson(int $salespersonId): int
    {
        $salesperson = User::findOrFail($salespersonId);

        if ($salesperson->hasRole(RoleEnum::LEADER->value) || $salesperson->hasRole(RoleEnum::ADMIN->value)) {
            return (int) $salesperson->id;
        }

        if (! empty($salesperson->leader_id)) {
            return (int) $salesperson->leader_id;
        }

        throw ValidationException::withMessages([
            'salesperson_id' => 'Selected salesperson must have an assigned leader.',
        ]);
    }
}
