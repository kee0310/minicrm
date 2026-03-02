<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'leader_id' => null,
        ]);
        $admin->syncRoles([RoleEnum::ADMIN->value]);
        $admin->forceFill(['leader_id' => $admin->id])->saveQuietly();

        $leader = User::factory()->create([
            'name' => 'Leader',
            'email' => 'leader@leader.com',
            'password' => bcrypt('password'),
            'leader_id' => null,
        ]);
        $leader->syncRoles([RoleEnum::LEADER->value]);
        $leader->forceFill(['leader_id' => $leader->id])->saveQuietly();

        User::factory()->create([
            'name' => 'Loan Officer',
            'email' => 'lofficer@lofficer.com',
            'password' => bcrypt('password'),
            'leader_id' => null,
        ])->syncRoles([RoleEnum::LOAN_OFFICER->value]);

        User::factory()->create([
            'name' => 'Salesperson',
            'email' => 'salesperson@salesperson.com',
            'password' => bcrypt('password'),
            'leader_id' => $leader->id,
        ])->syncRoles([RoleEnum::SALESPERSON->value]);

        User::factory(5)->create([
            'leader_id' => $leader->id,
        ]);
    }
}
