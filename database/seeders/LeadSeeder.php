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
    private const LEADS_PER_MONTH = [
        1 => 15,
        2 => 25,
    ];

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
            LeadStatusEnum::LOST->value,
            LeadStatusEnum::DEAL->value,
            LeadStatusEnum::DEAL->value,
            LeadStatusEnum::DEAL->value,
        ];

        $sequence = 1;
        foreach (self::LEADS_PER_MONTH as $month => $totalLeads) {
            for ($i = 1; $i <= $totalLeads; $i++) {
                /** @var User $salesperson */
                $salesperson = $salespeople->random();
                $status = $statuses[array_rand($statuses)];
                $createdAt = $this->randomSeedDateForMonth($month);

                $leaderId = $salesperson->leader_id;
                if ($salesperson->hasAnyRole([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])) {
                    $leaderId = $salesperson->id;
                } elseif (! $leaderId && $leaders->isNotEmpty()) {
                    $leaderId = $leaders->random()->id;
                }

                $dealDetails = [];
                if ($status === LeadStatusEnum::DEAL->value) {
                    $dealDetails = [
                        'age' => $faker->numberBetween(23, 65),
                        'ic_passport' => strtoupper($faker->bothify('#######?#')),
                        'occupation' => $faker->jobTitle(),
                        'company' => $faker->company(),
                        'working_years' => $faker->numberBetween(1, 25),
                        'monthly_income' => $faker->numberBetween(2500, 30000),
                        'fixed_income' => $faker->numberBetween(1500, 25000),
                    ];
                }

                $lead = Lead::factory()->create(array_merge([
                    'name' => $faker->name(),
                    'email' => sprintf('lead%03d@example.com', $sequence),
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
                ], $dealDetails));

                $lead->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $this->clampToSeedWindow($createdAt->copy()->addDays(random_int(0, 10))),
                ])->saveQuietly();

                $sequence++;
            }
        }
    }

    private function randomSeedDateForMonth(int $month): Carbon
    {
        $year = (int) now()->year;
        $start = Carbon::create($year, $month, 1, 8, 0, 0);
        $end = $start->copy()->endOfMonth()->setTime(20, 59, 59);

        return Carbon::instance(fake()->dateTimeBetween($start, $end));
    }

    private function clampToSeedWindow(Carbon $date): Carbon
    {
        $year = (int) now()->year;
        $start = Carbon::create($year, 1, 1, 0, 0, 0);
        $end = Carbon::create($year, 2, 1, 23, 59, 59)->endOfMonth();

        if ($date->lt($start)) {
            return $start;
        }

        if ($date->gt($end)) {
            return $end;
        }

        return $date;
    }
}
