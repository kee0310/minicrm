<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LoanPreQualification;
use Illuminate\Database\Seeder;

class LoanPreQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();

        $eligibleDeals = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->with('client')
            ->get();

        foreach ($eligibleDeals as $deal) {
            if (random_int(1, 100) > 85) {
                continue;
            }

            $income = (float) ($deal->client?->monthly_income ?? $faker->randomFloat(2, 3000, 22000));
            $isHighDsr = random_int(1, 100) <= 25;
            $ratio = $isHighDsr
                ? $faker->randomFloat(2, 0.70, 0.92)
                : $faker->randomFloat(2, 0.25, 0.65);
            $monthlyCommitments = round($income * $ratio, 2);

            LoanPreQualification::query()->updateOrCreate(
                ['deal_id' => $deal->id],
                [
                    'existing_loans' => $faker->randomFloat(2, 0, 500000),
                    'monthly_commitments' => $monthlyCommitments,
                    'credit_card_limits' => $faker->randomFloat(2, 5000, 50000),
                    'credit_card_utilization' => random_int(10, 95),
                    'ccris' => $faker->randomElement([
                        'clean record',
                        'good repayment',
                        'minor late payment in past',
                        'overdue account detected',
                    ]),
                    'ctos' => $faker->randomElement([
                        'no issues',
                        'clean',
                        'rescheduled account',
                        'current account',
                    ]),
                    'risk_grade' => $faker->randomElement(['A', 'B', 'C']),
                    'pre_qualification_date' => $deal->created_at?->copy()->addDays(random_int(1, 10))?->toDateString(),
                    'recommended_banks' => [
                        $faker->randomElement(['Maybank', 'CIMB', 'Public Bank', 'Hong Leong']),
                        $faker->randomElement(['RHB', 'AmBank', 'UOB', 'OCBC']),
                    ],
                ]
            );
        }
    }
}
