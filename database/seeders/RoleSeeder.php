<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => RoleEnum::ADMIN->value]);
        Role::firstOrCreate(['name' => RoleEnum::LEADER->value]);
        Role::firstOrCreate(['name' => RoleEnum::LOAN_OFFICER->value]);
        Role::firstOrCreate(['name' => RoleEnum::USER->value]);
    }
}
