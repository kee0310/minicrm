<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DealService
{
    public function createDeal(array $data): Deal
    {
        Lead::findOrFail($data['lead_id']);
        $this->assignDealOwnership($data);

        return Deal::create($data);
    }

    public function updateDeal(Deal $deal, array $data): void
    {
        $originalPipeline = $deal->pipeline?->value ?? (string) $deal->pipeline;
        Lead::findOrFail($data['lead_id']);
        $this->assignDealOwnership($data, $deal);

        $deal->update($data);

        if (isset($data['pipeline']) && (string) $data['pipeline'] !== (string) $originalPipeline) {
            $deal->syncPipelineStage((string) $data['pipeline']);
        }
    }

    public function deleteDeal(Deal $deal): void
    {
        $deal->delete();
    }

    public function assignDealOwnership(array &$data, ?Deal $existingDeal = null): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        $resolveLeaderId = function (?int $salespersonId) use ($user): ?int {
            if (! $salespersonId) {
                return $user?->leader_id ?? $user?->id;
            }

            $salesperson = User::find($salespersonId);
            if (! $salesperson) {
                return $user?->leader_id ?? $user?->id;
            }

            return $salesperson->leader_id ?: $salesperson->id;
        };

        if ($user?->hasRole(RoleEnum::SALESPERSON->value)) {
            $data['salesperson_id'] = $user->id;
            $data['leader_id'] = $resolveLeaderId($user->id);

            return;
        }

        if ($user?->hasRole(RoleEnum::LEADER->value)) {
            $data['salesperson_id'] = $data['salesperson_id'] ?? $existingDeal?->salesperson_id ?? $user->id;
            $data['leader_id'] = $resolveLeaderId($data['salesperson_id']);

            return;
        }

        $data['salesperson_id'] = $data['salesperson_id'] ?? $existingDeal?->salesperson_id ?? $user?->id;
        $data['leader_id'] = $resolveLeaderId($data['salesperson_id']);
    }

    public function assignableLeads(?User $user)
    {
        $leads = Lead::query();

        if (! $user || $user->hasRole(RoleEnum::ADMIN->value)) {
            return $leads->orderBy('name')->get(['id', 'name', 'email']);
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return $leads
                ->where(function ($query) use ($user) {
                    $query->where('leader_id', $user->id)
                        ->orWhere('salesperson_id', $user->id);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        return $leads
            ->where('salesperson_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function assignableSalespersons(?User $user)
    {
        if (! $user) {
            return collect();
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return User::query()
                ->role([RoleEnum::SALESPERSON->value, RoleEnum::LEADER->value])
                ->orderBy('name')
                ->get(['id', 'name', 'leader_id']);
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            $teamSalespersons = User::query()
                ->role(RoleEnum::SALESPERSON->value)
                ->where('leader_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'leader_id']);

            return $teamSalespersons->prepend($user)->unique('id')->values();
        }

        if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
            return User::query()->whereKey($user->id)->get(['id', 'name', 'leader_id']);
        }

        return collect();
    }
}
