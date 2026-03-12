<?php

namespace App\Services;

use App\Enums\LoanApprovalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LoanBankSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(private OfficerAssignmentService $officerAssignment) {}

    public function syncDealPipelineByApprovalStatus(Deal $deal, string $status): void
    {
        if ($status === LoanApprovalStatusEnum::SUBMITTED->value) {
            $deal->syncPipelineStage(PipelineEnum::LOAN_SUBMITTED);

            return;
        }

        if ($status === LoanApprovalStatusEnum::APPROVED->value) {
            $deal->syncPipelineStage(PipelineEnum::LOAN_APPROVED);
        }
    }

    public function persistApprovalAnalysis(Deal $deal, array $data): void
    {
        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        $submission->update([
            'approved_bank' => $data['approved_bank'] ?? null,
            'applied_amount' => $data['applied_amount'] ?? null,
            'approved_amount' => $data['approved_amount'] ?? null,
            'interest_rate' => $data['interest_rate'] ?? null,
            'lock_in_period' => $data['lock_in_period'] ?? null,
            'mrta_mlta' => $data['mrta_mlta'] ?? null,
            'special_conditions' => filled($data['special_conditions'] ?? null) ? $data['special_conditions'] : null,
        ]);
    }

    public function buildLoanDetailPayload(Deal $deal): array
    {
        $pipelineDates = DB::table('deal_pipelines')
            ->where('deal_id', $deal->id)
            ->first([
                'lead_date',
                'viewing_date',
                'booking_date',
                'spa_signed_date',
                'loan_submitted_date',
                'loan_approved_date',
                'legal_processing_date',
                'completed_date',
                'commission_paid_date',
            ]);

        $pre = $deal->preQualification;
        $client = $deal->client ?? Lead::find($deal->lead_id);
        $storedRecommendations = is_array($pre?->recommended_banks) ? $pre->recommended_banks : [];
        $hasStructuredRecommendations = ! empty($storedRecommendations)
            && is_array($storedRecommendations[0] ?? null)
            && array_key_exists('bank', $storedRecommendations[0]);

        $recommendations = $hasStructuredRecommendations
            ? collect([0, 1, 2])->map(fn($index) => [
                'bank' => $storedRecommendations[$index]['bank'] ?? null,
                'approval_probability' => $storedRecommendations[$index]['approval_probability'] ?? null,
                'loan_margin' => $storedRecommendations[$index]['loan_margin'] ?? null,
            ])->all()
            : collect([0, 1, 2])->map(fn($index) => [
                'bank' => $storedRecommendations[$index] ?? null,
                'approval_probability' => null,
                'loan_margin' => null,
            ])->all();

        $riskGrade = $pre?->riskGrade() ?? $pre?->risk_grade;
        $allLoanRows = $deal->bankSubmissions->sortBy('loan_id')->values();
        $legal = $deal->legalCase;

        return [
            'deal_code' => $deal->deal_id,
            'deal_status' => $deal->pipeline?->value,
            'project_name' => $deal->project_name,
            'developer' => $deal->developer,
            'salesperson_name' => $deal->salesperson?->name,
            'leader_name' => $deal->leader?->name,
            'loan_officer_name' => $deal->loanOfficer?->name,
            'legal_officer_name' => $deal->legalOfficer?->name,
            'unit_number' => $deal->unit_number,
            'selling_price' => $deal->selling_price,
            'created_at' => optional($deal->created_at)->format('Y-m-d'),
            'pipeline_dates' => [
                'lead_date' => $pipelineDates?->lead_date ? Carbon::parse($pipelineDates->lead_date)->format('Y-m-d') : null,
                'viewing_date' => $pipelineDates?->viewing_date ? Carbon::parse($pipelineDates->viewing_date)->format('Y-m-d') : null,
                'booking_date' => $pipelineDates?->booking_date ? Carbon::parse($pipelineDates->booking_date)->format('Y-m-d') : null,
                'spa_signed_date' => $pipelineDates?->spa_signed_date ? Carbon::parse($pipelineDates->spa_signed_date)->format('Y-m-d') : null,
                'loan_submitted_date' => $pipelineDates?->loan_submitted_date ? Carbon::parse($pipelineDates->loan_submitted_date)->format('Y-m-d') : null,
                'loan_approved_date' => $pipelineDates?->loan_approved_date ? Carbon::parse($pipelineDates->loan_approved_date)->format('Y-m-d') : null,
                'legal_processing_date' => $pipelineDates?->legal_processing_date ? Carbon::parse($pipelineDates->legal_processing_date)->format('Y-m-d') : null,
                'completed_date' => $pipelineDates?->completed_date ? Carbon::parse($pipelineDates->completed_date)->format('Y-m-d') : null,
                'commission_paid_date' => $pipelineDates?->commission_paid_date ? Carbon::parse($pipelineDates->commission_paid_date)->format('Y-m-d') : null,
            ],
            'client' => [
                'lead_id' => $deal->lead_id,
                'name' => $client?->name,
                'email' => $client?->email,
                'phone' => $client?->phone,
                'age' => $client?->age,
                'ic_passport' => $client?->ic_passport,
                'occupation' => $client?->occupation,
                'company' => $client?->company,
                'working_years' => $client?->working_years,
                'monthly_income' => $client?->monthly_income,
                'fixed_income' => $client?->fixed_income,
            ],
            'borrower_profile' => [
                'risk_grade' => $riskGrade,
                'existing_loans' => $pre?->existing_loans,
                'monthly_commitments' => $pre?->monthly_commitments,
                'credit_card_limits' => $pre?->credit_card_limits,
                'credit_card_utilization' => $pre?->credit_card_utilization,
                'ccris' => $pre?->ccris,
                'ctos' => $pre?->ctos,
            ],
            'pre_qualification' => [
                'date' => optional($pre?->pre_qualification_date)->format('Y-m-d'),
                'recommendations' => $recommendations,
            ],
            'bank_submissions' => $allLoanRows->map(fn($loan) => [
                'loan_id' => $loan->loan_id,
                'bank_name' => $loan->bank_name,
                'banker_contact' => $loan->banker_contact,
                'document_completeness_score' => $loan->document_completeness_score,
                'approval_status' => $loan->approval_status,
                'submission_date' => optional($loan->submission_date)->format('Y-m-d'),
                'expected_approval_date' => optional($loan->expected_approval_date)->format('Y-m-d'),
                'file_completeness_percentage' => is_null($loan->file_completeness_percentage) ? null : ($loan->file_completeness_percentage . '%'),
            ])->all(),
            'approval_analysis' => $allLoanRows->filter(
                fn($loan) => ! is_null($loan->approved_bank) || ! is_null($loan->applied_amount) || ! is_null($loan->approved_amount)
            )->map(fn($loan) => [
                'loan_id' => $loan->loan_id,
                'approved_bank' => $loan->approved_bank ?? $loan->bank_name,
                'applied_amount' => $loan->applied_amount,
                'approved_amount' => $loan->approved_amount,
                'interest_rate' => $loan->interest_rate,
                'lock_in_period' => $loan->lock_in_period,
            ])->values()->all(),
            'disbursements' => $allLoanRows->filter(
                fn($loan) => ! is_null($loan->first_disbursement_date) || ! is_null($loan->full_disbursement_date) || ! is_null($loan->spa_completion_date) || ! is_null($loan->client_notification_date)
            )->map(fn($loan) => [
                'loan_id' => $loan->loan_id,
                'first_disbursement_date' => optional($loan->first_disbursement_date)->format('Y-m-d'),
                'full_disbursement_date' => optional($loan->full_disbursement_date)->format('Y-m-d'),
                'spa_completion_date' => optional($loan->spa_completion_date)->format('Y-m-d'),
                'client_notification_date' => optional($loan->client_notification_date)->format('Y-m-d'),
            ])->values()->all(),
            'legal' => [
                'status' => $legal?->status,
                'lawyer_firm' => $legal?->lawyer_firm,
                'spa_date' => optional($legal?->spa_date)->format('Y-m-d'),
                'loan_agreement_date' => optional($legal?->loan_agreement_date)->format('Y-m-d'),
                'completion_date' => optional($legal?->completion_date)->format('Y-m-d'),
                'stamp_duty' => $legal?->stamp_duty,
            ],
        ];
    }

    public function updateBorrowerProfile(Deal $deal, array $data, ?User $user): string
    {
        $preQualification = $deal->preQualification()->firstOrCreate([]);
        $preQualification->fill(collect($data)->except('assign_to')->all());
        $preQualification->risk_grade = $preQualification->riskGrade();
        $preQualification->save();

        $this->officerAssignment->assignOfficerIfNeeded(
            $deal,
            $user,
            isset($data['assign_to']) ? (int) $data['assign_to'] : null,
            RoleEnum::LOAN_OFFICER->value,
            'loan_officer_id'
        );

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);

        return "Borrower profile for deal {$dealCode} updated successfully.";
    }

    public function updatePreQualification(Deal $deal, array $data): string
    {
        $recommendations = collect([1, 2, 3])->map(fn(int $index) => [
            'bank' => $data["recommended_bank_{$index}"] ?? null,
            'approval_probability' => $data["approval_probability_{$index}"] ?? null,
            'loan_margin' => $data["loan_margin_{$index}"] ?? null,
        ])->all();

        $deal->preQualification()->updateOrCreate(
            ['deal_id' => $deal->id],
            [
                'pre_qualification_date' => $data['pre_qualification_date'] ?? null,
                'recommended_banks' => $recommendations,
            ]
        );

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);

        return "Pre-qualification for deal {$dealCode} updated successfully.";
    }

    public function createBankSubmission(Deal $deal, array $data): LoanBankSubmission
    {
        $payload = collect($data)->except('deal_id')->all();

        $submission = $deal->bankSubmissions()->create($payload);
        $this->syncDealPipelineByApprovalStatus($deal, $submission->approval_status);

        return $submission;
    }

    public function updateBankSubmission(LoanBankSubmission $submission, Deal $deal, array $data): void
    {
        $payload = collect($data)->except('deal_id')->all();

        $submission->update($payload);
        $this->syncDealPipelineByApprovalStatus($submission->deal, $submission->approval_status);
    }

    public function updateDisbursement(Deal $deal, array $data): string
    {
        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        $submission->update([
            'first_disbursement_date' => $data['first_disbursement_date'] ?? null,
            'full_disbursement_date' => $data['full_disbursement_date'] ?? null,
            'spa_completion_date' => $data['spa_completion_date'] ?? null,
            'client_notification_date' => $data['client_notification_date'] ?? null,
        ]);

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);

        return "Disbursement details for deal {$dealCode} updated successfully.";
    }

    /**
     * @param  Builder<LoanBankSubmission>  $scopedQuery
     */
    public function buildBankApprovalRates(Builder $scopedQuery, array $bankOptions): array
    {
        $bankApprovalRateRows = $scopedQuery
            ->whereNotNull('bank_name')
            ->selectRaw('bank_name, SUM(CASE WHEN approval_status = ? THEN 1 ELSE 0 END) as approved_count', [LoanApprovalStatusEnum::APPROVED->value])
            ->selectRaw(
                'SUM(CASE WHEN approval_status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) as submitted_count',
                LoanApprovalStatusEnum::submittedToBank()
            )
            ->groupBy('bank_name')
            ->get();

        $bankApprovalRatesByName = $bankApprovalRateRows->mapWithKeys(function ($row) {
            $submittedCount = (int) ($row->submitted_count ?? 0);
            $approvedCount = (int) ($row->approved_count ?? 0);
            $approvalRate = $submittedCount > 0
                ? round(($approvedCount / $submittedCount) * 100, 2)
                : 0.0;

            return [
                (string) $row->bank_name => [
                    'bank' => (string) $row->bank_name,
                    'approved_count' => $approvedCount,
                    'submitted_count' => $submittedCount,
                    'approval_rate' => $approvalRate,
                ],
            ];
        });

        return collect($bankOptions)
            ->merge($bankApprovalRatesByName->keys())
            ->unique()
            ->values()
            ->map(fn(string $bank) => $bankApprovalRatesByName[$bank] ?? [
                'bank' => $bank,
                'approved_count' => 0,
                'submitted_count' => 0,
                'approval_rate' => 0.0,
            ])
            ->all();
    }

    /**
     * @param  Builder<Deal>  $scopedQuery
     */
    public function eligibleDealsForBankSubmission(bool $canManageLoanRecords, Builder $scopedQuery)
    {
        if (! $canManageLoanRecords) {
            return collect();
        }

        return $scopedQuery
            ->with('client:id,name')
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
            ])
            ->latest('updated_at')
            ->get(['id', 'deal_id', 'project_name', 'lead_id']);
    }

    // Submission date is managed directly on the loan submission record.
}
