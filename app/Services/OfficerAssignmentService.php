<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\User;

class OfficerAssignmentService
{
    public function isCaseTaken(
        Deal $deal,
        ?User $user,
        string $requiredRole,
        string $officerColumn
    ): bool {
        if (! $user) {
            return false;
        }

        if (! $user->hasRole($requiredRole) || $user->hasRole(RoleEnum::ADMIN->value)) {
            return false;
        }

        $currentOfficerId = $deal->{$officerColumn};

        return ! is_null($currentOfficerId) && (int) $currentOfficerId !== (int) $user->id;
    }

    public function assignOfficerIfNeeded(
        Deal $deal,
        ?User $user,
        ?int $assignToId,
        string $requiredRole,
        string $officerColumn
    ): void {
        if (
            $user?->hasRole($requiredRole)
            && ! $user->hasRole(RoleEnum::ADMIN->value)
            && is_null($deal->{$officerColumn})
        ) {
            $deal->update([$officerColumn => (int) $user->id]);
        }

        if (! is_null($assignToId)) {
            $deal->update([$officerColumn => (int) $assignToId]);
        }
    }
}
