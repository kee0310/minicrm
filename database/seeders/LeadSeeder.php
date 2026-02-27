<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salespersonIds = User::role([
            RoleEnum::USER->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->pluck('id');

        $leaderIds = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->pluck('id');

        Lead::factory(30)->make()->each(function (Lead $lead) use ($salespersonIds, $leaderIds) {
            $lead->salesperson_id = $salespersonIds->random();
            $lead->leader_id = $leaderIds->isNotEmpty() ? $leaderIds->random() : null;
            $lead->save();
        });
    }
}
