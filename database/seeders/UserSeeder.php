<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\RoleEnum;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ])->syncRoles([RoleEnum::ADMIN->value]);

        User::factory()->create([
            'name' => 'Leader',
            'email' => 'leader@leader.com',
            'password' => bcrypt('password'),
        ])->syncRoles([RoleEnum::LEADER->value]);

        User::factory()->create([
            'name' => 'Loan Officer',
            'email' => 'loan@loan.com',
            'password' => bcrypt('password'),
        ])->syncRoles([RoleEnum::LOAN_OFFICER->value]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => bcrypt('password'),
        ])->syncRoles([RoleEnum::USER->value]);

        User::factory(5)->create();
    }
}
