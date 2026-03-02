<?php

namespace Database\Seeders;

use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salespeople = User::role([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->get();

        $leaders = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->get();

        if ($salespeople->isEmpty()) {
            return;
        }

        $faker = fake();
        $statuses = [
            LeadStatusEnum::NEW->value,
            LeadStatusEnum::CONTACTED->value,
            LeadStatusEnum::SCHEDULED->value,
            LeadStatusEnum::DEAL->value,
            LeadStatusEnum::LOST->value,
            LeadStatusEnum::DEAL->value,
            LeadStatusEnum::DEAL->value,
        ];

        for ($i = 0; $i < 140; $i++) {
            /** @var User $salesperson */
            $salesperson = $salespeople->random();
            $status = $statuses[array_rand($statuses)];
            $createdAt = Carbon::now()->subDays(random_int(0, 240))->setTime(random_int(8, 20), random_int(0, 59));

            $leaderId = $salesperson->leader_id;
            if ($salesperson->hasAnyRole([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])) {
                $leaderId = $salesperson->id;
            } elseif (!$leaderId && $leaders->isNotEmpty()) {
                $leaderId = $leaders->random()->id;
            }

            $lead = Lead::factory()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->numerify('01#########'),
                'source' => $faker->randomElement([
                    'Facebook',
                    'Friend Referral',
                    'Exhibition/Fair',
                    'Company Assigned',
                    'Old Client Referral',
                ]),
                'salesperson_id' => $salesperson->id,
                'leader_id' => $leaderId,
                'status' => $status,
            ]);

            $lead->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(random_int(0, 20)),
            ])->saveQuietly();
        }
    }
}
