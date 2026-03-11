<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class OfficerDirectoryService
{
    /**
     * @return array{0: Collection<int, User>, 1: int|null}
     */
    public function listAndCurrent(?User $user, string $role): array
    {
        $officers = User::role($role)->orderBy('name')->get(['id', 'name']);
        $currentId = $user?->hasRole($role)
            ? $user->id
            : ($officers->first()?->id);

        return [$officers, $currentId];
    }

    /**
     * @return array<int, int>
     */
    public function idsForRole(string $role): array
    {
        return User::role($role)->pluck('id')->all();
    }
}
