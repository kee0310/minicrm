<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Arr;

class UserService
{
    public function createUser(array $data): User
    {
        $role = $data['role'] ?? null;
        $userData = Arr::except($data, ['role']);
        $userData['leader_id'] = $role === RoleEnum::SALESPERSON->value ? ($data['leader_id'] ?? null) : null;

        $user = User::create($userData);
        if (! empty($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function updateUser(User $user, array $data): void
    {
        $role = $data['role'] ?? null;
        $userData = Arr::except($data, ['role']);
        $userData['leader_id'] = $role === RoleEnum::SALESPERSON->value ? ($data['leader_id'] ?? null) : null;

        $user->update($userData);
        if (! empty($role)) {
            $user->syncRoles([$role]);
        }
    }

    public function deleteUser(User $user): ?string
    {
        if ($user->salesDeals()->exists()) {
            return "User {$user->name} cannot be deleted because they have deals assigned.";
        }

        if ($user->salesLeads()->exists()) {
            return "User {$user->name} cannot be deleted because they have leads assigned.";
        }

        $user->delete();

        return null;
    }
}
