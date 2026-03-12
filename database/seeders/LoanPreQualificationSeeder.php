<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LoanPreQualification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            $income = (float) ($deal->client?->monthly_income ?? $faker->randomFloat(2, 2800, 24000));
            $existingLoans = $faker->randomFloat(2, 0, max(15000, $income * 18));
            $monthlyCommitments = round($income * $faker->randomFloat(2, 0.18, 0.75), 2);
            $creditCardLimits = $faker->randomFloat(2, 3000, 60000);

            $pipelineDates = DB::table('deal_pipelines')
                ->where('deal_id', $deal->id)
                ->first(['booking_date', 'spa_signed_date', 'lead_date']);

            $baseDate = Carbon::parse(
                $pipelineDates?->booking_date
                    ?? $pipelineDates?->spa_signed_date
                    ?? $pipelineDates?->lead_date
                    ?? $deal->created_at
            );
            $preQualificationDate = $this->clampToSeedWindow($baseDate->copy()->addDays(random_int(0, 12)));

            $preQualification = LoanPreQualification::query()->updateOrCreate(
                ['deal_id' => $deal->id],
                [
                    'existing_loans' => $existingLoans,
                    'monthly_commitments' => $monthlyCommitments,
                    'credit_card_limits' => $creditCardLimits,
                    'credit_card_utilization' => random_int(5, 92),
                    'ccris' => $faker->randomElement([
                        'clean record',
                        'good repayment',
                        'no overdue',
                        'minor late payment history',
                        'rescheduled account',
                    ]),
                    'ctos' => $faker->randomElement([
                        'no issues',
                        'clear',
                        'current account',
                        'special attention account',
                    ]),
                    'pre_qualification_date' => $preQualificationDate,
                    'recommended_banks' => [
                        $faker->randomElement(['Maybank', 'CIMB', 'Public Bank', 'Hong Leong']),
                        $faker->randomElement(['RHB', 'AmBank', 'UOB', 'OCBC']),
                    ],
                    'created_at' => $this->clampToSeedWindow($deal->created_at?->copy() ?? now()),
                    'updated_at' => $this->clampToSeedWindow(($deal->updated_at?->copy() ?? now())->addDays(random_int(0, 5))),
                ]
            );

            $riskGrade = $preQualification->riskGrade();
            if (! is_null($riskGrade)) {
                $preQualification->forceFill(['risk_grade' => $riskGrade])->saveQuietly();
            }
        }
    }

    private function clampToSeedWindow(CarbonInterface $date): CarbonInterface
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
