<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoanBankSubmissionSeeder extends Seeder
{
    private const STAGE_ORDER = [
        PipelineEnum::LEAD->value => 0,
        PipelineEnum::VIEWING->value => 1,
        PipelineEnum::BOOKING->value => 2,
        PipelineEnum::SPA_SIGNED->value => 3,
        PipelineEnum::LOAN_SUBMITTED->value => 4,
        PipelineEnum::LOAN_APPROVED->value => 5,
        PipelineEnum::LEGAL_PROCESSING->value => 6,
        PipelineEnum::COMPLETED->value => 7,
        PipelineEnum::COMMISSION_PAID->value => 8,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();

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
            $stageIndex = self::STAGE_ORDER[$deal->pipeline?->value ?? (string) $deal->pipeline] ?? 0;
            $pipelineDates = DB::table('deal_pipelines')
                ->where('deal_id', $deal->id)
                ->first(['loan_submitted_date', 'completed_date', 'legal_processing_date', 'spa_signed_date']);

            $submissionDate = $this->clampToSeedWindow(
                Carbon::parse($pipelineDates?->loan_submitted_date ?? $deal->updated_at ?? $deal->created_at)->copy()
            );
            $applicationAmount = round((float) $deal->selling_price * $faker->randomFloat(4, 0.75, 0.95), 2);

            $approvalStatus = match (true) {
                $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] => 'Approved',
                default => $faker->randomElement(['Submitted', 'In Review', 'Rejected']),
            };

            $approvedAmount = null;
            if ($stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] && $approvalStatus === 'Approved') {
                $approvedAmount = round($applicationAmount * $faker->randomFloat(4, 0.80, 1.00), 2);
            }

            $fullDisbursementDate = null;
            if ($stageIndex >= self::STAGE_ORDER[PipelineEnum::COMPLETED->value]) {
                $fullDisbursementDate = $this->clampToSeedWindow(Carbon::parse(
                    $pipelineDates?->completed_date ?? $pipelineDates?->legal_processing_date ?? $submissionDate
                ));
            }

            $expectedApprovalDate = $this->clampToSeedWindow($submissionDate->copy()->addDays(random_int(7, 25)));
            $spaCompletionDate = $stageIndex >= self::STAGE_ORDER[PipelineEnum::COMPLETED->value]
                ? $this->clampToSeedWindow(Carbon::parse($pipelineDates?->spa_signed_date ?? $submissionDate)->addDays(random_int(20, 40)))
                : null;
            $clientNotificationDate = $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value]
                ? $this->clampToSeedWindow($submissionDate->copy()->addDays(random_int(10, 20)))
                : null;
            $firstDisbursementDate = $fullDisbursementDate
                ? $this->clampToSeedWindow($fullDisbursementDate->copy()->subDays(random_int(7, 20)))
                : null;

            LoanBankSubmission::query()->updateOrCreate(
                ['deal_id' => $deal->id],
                [
                    'bank_name' => $faker->randomElement(['Maybank', 'CIMB', 'Public Bank', 'RHB', 'Hong Leong', 'UOB']),
                    'banker_contact' => $faker->name(),
                    'submission_date' => $submissionDate,
                    'document_completeness_score' => random_int(1, 5),
                    'approval_status' => $approvalStatus,
                    'expected_approval_date' => $expectedApprovalDate,
                    'file_completeness_percentage' => random_int(55, 100),
                    'approved_bank' => $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] ? $faker->randomElement(['Maybank', 'CIMB', 'Public Bank', 'RHB', 'Hong Leong', 'UOB']) : null,
                    'application_amount' => $applicationAmount,
                    'applied_amount' => $applicationAmount,
                    'approved_amount' => $approvedAmount,
                    'interest_rate' => $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] ? $faker->randomFloat(2, 3.0, 5.2) : null,
                    'lock_in_period' => $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] ? $faker->randomElement(['3 years', '5 years']) : null,
                    'mrta_mlta' => $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] ? $faker->randomElement(['MRTA', 'MLTA', 'None']) : null,
                    'special_conditions' => $stageIndex >= self::STAGE_ORDER[PipelineEnum::LOAN_APPROVED->value] ? $faker->optional(0.7)->sentence() : null,
                    'first_disbursement_date' => $firstDisbursementDate,
                    'full_disbursement_date' => $fullDisbursementDate,
                    'spa_completion_date' => $spaCompletionDate,
                    'client_notification_date' => $clientNotificationDate,
                    'created_at' => $submissionDate,
                    'updated_at' => $this->clampToSeedWindow($submissionDate->copy()->addDays(random_int(0, 7))),
                ]
            );
        }
    }

    private function clampToSeedWindow(\Carbon\CarbonInterface $date): \Carbon\CarbonInterface
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
