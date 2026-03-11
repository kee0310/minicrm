<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DealSeeder extends Seeder
{
    private const DEALS_PER_MONTH = [
        1 => 10,
        2 => 20,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leads = Lead::query()->get();

        $salespeople = User::role([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->get();

        $leaders = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->get();

        $loanOfficers = User::role(RoleEnum::LOAN_OFFICER->value)->get();
        $legalOfficers = User::role(RoleEnum::LEGAL_OFFICER->value)->get();

        if ($leads->isEmpty() || $salespeople->isEmpty() || $leaders->isEmpty()) {
            return;
        }

        $faker = fake();
        foreach (self::DEALS_PER_MONTH as $month => $totalDeals) {
            for ($i = 1; $i <= $totalDeals; $i++) {
                /** @var Lead $lead */
                $lead = $leads->random();
                /** @var User $salesperson */
                $salesperson = $salespeople->random();
                /** @var User $leader */
                $leader = $leaders->firstWhere('id', $salesperson->leader_id) ?? $leaders->random();

                $stage = PipelineEnum::COMPLETED->value;
                $createdAt = $this->randomSeedDateForMonth($month);
                $completedAt = $this->clampToSeedWindow($createdAt->copy()->addDays(random_int(7, 15)));

                $sellingPrice = $faker->randomFloat(2, 180000, 2800000);
                $commissionPercentage = $faker->randomFloat(2, 1.5, 4.5);
                $commissionAmount = round(($sellingPrice * $commissionPercentage) / 100, 2);

                $dealId = DB::table('deals')->insertGetId([
                    'lead_id' => $lead->id,
                    'project_name' => $faker->city() . ' ' . $faker->randomElement([
                        'Residences',
                        'Tower',
                        'Heights',
                        'Gardens',
                        'Sentral',
                        'Square',
                    ]),
                    'developer' => $faker->company(),
                    'unit_number' => $faker->bothify('##-##-###'),
                    'selling_price' => $sellingPrice,
                    'commission_percentage' => $commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'salesperson_id' => $salesperson->id,
                    'leader_id' => $leader->id,
                    'loan_officer_id' => $loanOfficers->isNotEmpty() ? $loanOfficers->random()->id : null,
                    'legal_officer_id' => $legalOfficers->isNotEmpty() ? $legalOfficers->random()->id : null,
                    'booking_fee' => $faker->randomFloat(2, 3000, 25000),
                    'deal_closing_date' => $completedAt->toDateString(),
                    'pipeline' => $stage,
                    'created_at' => $createdAt,
                    'updated_at' => $completedAt,
                ]);

                DB::table('deals')
                    ->where('id', $dealId)
                    ->update(['deal_id' => sprintf('DL-%06d', $dealId)]);
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
