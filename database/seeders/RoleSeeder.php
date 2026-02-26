<?php

namespace Database\Seeders;

use App\RoleEnum;
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
        Role::firstOrCreate(['name' => RoleEnum::ADMIN]);
        Role::firstOrCreate(['name' => RoleEnum::LEADER]);
        Role::firstOrCreate(['name' => RoleEnum::USER]);
    }
}
