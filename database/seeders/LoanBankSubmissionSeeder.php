<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoanBankSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();
        $today = Carbon::now();

        $loanDeals = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->get();

        foreach ($loanDeals as $deal) {
            $submissionDate = ($deal->created_at ?? $today)->copy()->addDays(random_int(5, 25));
            $status = $this->resolveStatus($deal->pipeline?->value ?? (string) $deal->pipeline);

            $appliedAmount = $faker->randomFloat(2, 120000, 1800000);
            $approvedAmount = $status === 'Approved'
                ? round($appliedAmount * $faker->randomFloat(4, 0.78, 0.95), 2)
                : null;

            $fullDisbursementDate = null;
            if (in_array($deal->pipeline?->value ?? (string) $deal->pipeline, [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ], true) && $approvedAmount) {
                $fullDisbursementDate = $submissionDate->copy()->addDays(random_int(30, 120));
            } elseif ($status === 'Approved' && random_int(1, 100) <= 35) {
                $fullDisbursementDate = $today->copy()->addDays(random_int(2, 30));
            }

            $loan = LoanBankSubmission::query()->create([
                'deal_id' => $deal->id,
                'bank_name' => $faker->randomElement([
                    'Maybank',
                    'CIMB',
                    'Public Bank',
                    'RHB',
                    'Hong Leong',
                    'UOB',
                ]),
                'banker_contact' => $faker->name(),
                'submission_date' => $submissionDate->toDateString(),
                'document_completeness_score' => random_int(40, 100),
                'approval_status' => $status,
                'expected_approval_date' => $submissionDate->copy()->addDays(random_int(5, 25))->toDateString(),
                'file_completeness_percentage' => random_int(45, 100),
                'approved_bank' => $status === 'Approved' ? $faker->randomElement([
                    'Maybank',
                    'CIMB',
                    'Public Bank',
                    'RHB',
                    'Hong Leong',
                    'UOB',
                ]) : null,
                'applied_amount' => $appliedAmount,
                'approved_amount' => $approvedAmount,
                'interest_rate' => $status === 'Approved' ? $faker->randomFloat(2, 3.0, 5.2) : null,
                'lock_in_period' => $status === 'Approved' ? $faker->randomElement(['3 years', '5 years']) : null,
                'mrta_mlta' => $status === 'Approved' ? $faker->randomElement(['MRTA', 'MLTA', 'None']) : null,
                'special_conditions' => $status === 'Approved' ? $faker->optional(0.6)->sentence() : null,
                'approval_deviation_percentage' => $status === 'Approved' ? $faker->randomFloat(2, -10, 8) : null,
                'first_disbursement_date' => $fullDisbursementDate
                    ? $fullDisbursementDate->copy()->subDays(random_int(5, 20))->toDateString()
                    : null,
                'full_disbursement_date' => $fullDisbursementDate?->toDateString(),
                'spa_completion_date' => $deal->spa_date?->toDateString(),
                'client_notification_date' => $status === 'Approved'
                    ? $submissionDate->copy()->addDays(random_int(10, 30))->toDateString()
                    : null,
            ]);

            $loan->forceFill([
                'created_at' => $submissionDate,
                'updated_at' => $submissionDate->copy()->addDays(random_int(0, 30)),
            ])->saveQuietly();

            if ($status === 'Rejected' && random_int(1, 100) <= 35) {
                $resubmissionDate = $submissionDate->copy()->addDays(random_int(7, 25));
                LoanBankSubmission::query()->create([
                    'deal_id' => $deal->id,
                    'bank_name' => $faker->randomElement(['Maybank', 'CIMB', 'Public Bank', 'RHB']),
                    'banker_contact' => $faker->name(),
                    'submission_date' => $resubmissionDate->toDateString(),
                    'document_completeness_score' => random_int(55, 100),
                    'approval_status' => $faker->randomElement(['In Review', 'Approved']),
                    'expected_approval_date' => $resubmissionDate->copy()->addDays(random_int(5, 20))->toDateString(),
                    'file_completeness_percentage' => random_int(60, 100),
                    'applied_amount' => $appliedAmount,
                ]);
            }
        }
    }

    private function resolveStatus(string $pipeline): string
    {
        return match ($pipeline) {
            PipelineEnum::LOAN_SUBMITTED->value => fake()->randomElement(['Submitted', 'In Review', 'Rejected']),
            PipelineEnum::LOAN_APPROVED->value,
            PipelineEnum::LEGAL_PROCESSING->value,
            PipelineEnum::COMPLETED->value,
            PipelineEnum::COMMISSION_PAID->value => 'Approved',
            default => 'Prepared',
        };
    }
}
