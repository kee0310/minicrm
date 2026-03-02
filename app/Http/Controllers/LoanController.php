<?php

namespace App\Http\Controllers;

use App\Enums\BankEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    // Borrower profile grid + financial/risk updates.
    public function borrowerProfile()
    {
        $deals = $this->scopeDealsForLoanAccess(
            Deal::with(['client', 'preQualification'])
        )
            ->latest()
            ->get();
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();

        return view('loans.borrower-profile', compact('deals', 'newCaseCounts', 'canManageLoanRecords'));
    }

    // Return one normalized loan detail payload for report modal.
    public function loanDetail(Deal $deal)
    {
        $this->ensureCanViewDeal($deal);
        $deal->loadMissing(['client', 'preQualification', 'bankSubmissions', 'salesperson', 'leader', 'legalCase']);

        return response()->json([
            'data' => $this->buildLoanDetailPayload($deal),
        ]);
    }

    // Return loan detail payload by loan_id (for pages that are keyed by loan rows).
    public function loanDetailByLoanId(string $loanId)
    {
        $submission = LoanBankSubmission::with(['deal.client', 'deal.preQualification', 'deal.bankSubmissions', 'deal.salesperson', 'deal.leader', 'deal.legalCase'])
            ->where('loan_id', $loanId)
            ->firstOrFail();
        $this->ensureCanViewDeal($submission->deal);

        return response()->json([
            'data' => $this->buildLoanDetailPayload($submission->deal),
        ]);
    }

    // Validate and persist borrower financial metrics, then refresh risk grade.
    public function updateBorrowerProfile(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        $data = $request->validate([
            'existing_loans' => ['required', 'numeric', 'min:0'],
            'monthly_commitments' => ['required', 'numeric', 'min:0'],
            'credit_card_limits' => ['required', 'numeric', 'min:0'],
            'credit_card_utilization' => ['required', 'numeric', 'min:0', 'max:100'],
            'ccris' => ['required', 'string', 'max:500'],
            'ctos' => ['required', 'string', 'max:500'],
        ]);

        $preQualification = $deal->preQualification()->firstOrCreate([]);
        $preQualification->fill($data);
        $preQualification->risk_grade = $preQualification->riskGrade();
        $preQualification->save();

        return redirect()->route('loans.borrower-profile')->with('success', 'Borrower profile updated.');
    }

    // Render pre-qualification table with deal, client risk, and bank options.
    public function preQualification()
    {
        $deals = $this->scopeDealsForLoanAccess(
            Deal::with(['preQualification', 'client'])
        )
            ->whereIn('pipeline', PipelineEnum::creatableValues())
            ->latest()
            ->get();
        $bankOptions = BankEnum::values();
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();

        return view('loans.pre-qualification', compact('deals', 'bankOptions', 'newCaseCounts', 'canManageLoanRecords'));
    }

    // Save three-slot bank recommendations and pre-qualification date for a deal.
    public function updatePreQualification(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        $data = $request->validate([
            'pre_qualification_date' => ['required', 'date'],
            'recommended_bank_1' => ['required', Rule::in(BankEnum::values())],
            'recommended_bank_2' => ['required', Rule::in(BankEnum::values())],
            'recommended_bank_3' => ['required', Rule::in(BankEnum::values())],
            'approval_probability_1' => ['required', 'integer', 'min:0', 'max:100'],
            'approval_probability_2' => ['required', 'integer', 'min:0', 'max:100'],
            'approval_probability_3' => ['required', 'integer', 'min:0', 'max:100'],
            'loan_margin_1' => ['required', 'integer', 'in:70,80,90'],
            'loan_margin_2' => ['required', 'integer', 'in:70,80,90'],
            'loan_margin_3' => ['required', 'integer', 'in:70,80,90'],
        ]);

        // Keep three recommendation slots with aligned bank/probability/margin payload.
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

        return redirect()->route('loans.pre-qualification')->with('success', 'Pre-qualification updated.');
    }

    // Render bank submission tracking with submission status options.
    public function bankSubmissionTracking()
    {
        $deals = $this->scopeDealsForLoanAccess(
            Deal::with(['bankSubmissions', 'client', 'preQualification'])
        )
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->latest()
            ->get();

        $bankOptions = BankEnum::values();
        $statusOptions = ['Prepared', 'Submitted', 'In Review', 'Approved', 'Rejected'];
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();
        $eligibleDeals = collect();

        if ($canManageLoanRecords) {
            $eligibleDeals = $this->scopeDealsForLoanAccess(
                Deal::with('client')->whereIn('pipeline', [
                    PipelineEnum::BOOKING->value,
                    PipelineEnum::SPA_SIGNED->value,
                    PipelineEnum::LOAN_SUBMITTED->value,
                ])
            )
                ->latest()
                ->get();
        }

        return view('loans.bank-submission-tracking', compact('deals', 'bankOptions', 'statusOptions', 'newCaseCounts', 'canManageLoanRecords', 'eligibleDeals'));
    }

    // Create a bank submission row and propagate workflow side effects.
    public function storeBankSubmission(Request $request)
    {
        $this->ensureCanManageLoanRecords();

        $data = $request->validate([
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['required', 'string', 'max:255'],
            'submission_date' => ['required', 'date'],
            'document_completeness_score' => ['required', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', 'in:Prepared,Submitted,In Review,Approved,Rejected'],
            'expected_approval_date' => ['required', 'date'],
            'file_completeness_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $deal = $this->scopeDealsForLoanAccess(Deal::query())
            ->whereKey($data['deal_id'])
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
            ])
            ->firstOrFail();

        $submission = $deal->bankSubmissions()->create(collect($data)->except('deal_id')->all());
        $this->syncDealPipelineByApprovalStatus($deal, $submission->approval_status);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission added.');
    }

    // Update an existing bank submission and re-apply workflow side effects.
    public function updateBankSubmission(Request $request, LoanBankSubmission $submission)
    {
        $this->ensureCanManageLoanRecords();

        $data = $request->validate([
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['required', 'string', 'max:255'],
            'submission_date' => ['required', 'date'],
            'document_completeness_score' => ['required', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', 'in:Prepared,Submitted,In Review,Approved,Rejected'],
            'expected_approval_date' => ['required', 'date'],
            'file_completeness_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $deal = $this->scopeDealsForLoanAccess(Deal::query())
            ->whereKey($data['deal_id'])
            ->firstOrFail();

        if ((int) $submission->deal_id !== (int) $deal->id) {
            abort(422, 'Invalid deal selected.');
        }

        $submission->update(collect($data)->except('deal_id')->all());
        $this->syncDealPipelineByApprovalStatus($submission->deal, $submission->approval_status);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission updated.');
    }

    // Render approval analysis rows for approved submissions (per loan_id).
    public function approvalAnalysis()
    {
        // Approval Analysis rows are driven directly from approved loans.
        $approvedSubmissions = $this->scopeLoanSubmissionsForLoanAccess(
            LoanBankSubmission::with(['deal.client', 'deal.preQualification', 'deal.bankSubmissions'])
        )
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();
        $bankOptions = BankEnum::values();
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();

        return view('loans.approval-analysis', compact('approvedSubmissions', 'bankOptions', 'newCaseCounts', 'canManageLoanRecords'));
    }

    // Create/update approval analysis details for the selected loan.
    public function storeApprovalAnalysis(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        $data = $request->validate([
            'loan_id' => ['required', 'string', 'exists:loans,loan_id'],
            'approved_bank' => ['required', Rule::in(BankEnum::values())],
            'applied_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lock_in_period' => ['required', 'string', 'max:255'],
            'mrta_mlta' => ['required', 'string', 'max:255'],
            'special_conditions' => ['required', 'string'],
        ]);

        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        $appliedAmount = (float) ($data['applied_amount'] ?? 0);
        $approvedAmount = (float) ($data['approved_amount'] ?? 0);
        $deviation = null;

        if ($appliedAmount > 0) {
            $deviation = round((($approvedAmount - $appliedAmount) / $appliedAmount) * 100, 2);
        }

        $submission->update([
            'approved_bank' => $data['approved_bank'] ?? null,
            'applied_amount' => $data['applied_amount'] ?? null,
            'approved_amount' => $data['approved_amount'] ?? null,
            'interest_rate' => $data['interest_rate'] ?? null,
            'lock_in_period' => $data['lock_in_period'] ?? null,
            'mrta_mlta' => $data['mrta_mlta'] ?? null,
            'special_conditions' => $data['special_conditions'] ?? null,
            'approval_deviation_percentage' => $deviation,
        ]);

        return redirect()->route('loans.approval-analysis')->with('success', 'Approval analysis added.');
    }

    // Update approval analysis details for the selected loan.
    public function updateApprovalAnalysis(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        $data = $request->validate([
            'loan_id' => ['required', 'string', 'exists:loans,loan_id'],
            'approved_bank' => ['required', Rule::in(BankEnum::values())],
            'applied_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lock_in_period' => ['required', 'string', 'max:255'],
            'mrta_mlta' => ['required', 'string', 'max:255'],
            'special_conditions' => ['required', 'string'],
        ]);

        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        $appliedAmount = (float) ($data['applied_amount'] ?? 0);
        $approvedAmount = (float) ($data['approved_amount'] ?? 0);
        $deviation = null;

        if ($appliedAmount > 0) {
            $deviation = round((($approvedAmount - $appliedAmount) / $appliedAmount) * 100, 2);
        }

        $submission->update([
            'approved_bank' => $data['approved_bank'] ?? null,
            'applied_amount' => $data['applied_amount'] ?? null,
            'approved_amount' => $data['approved_amount'] ?? null,
            'interest_rate' => $data['interest_rate'] ?? null,
            'lock_in_period' => $data['lock_in_period'] ?? null,
            'mrta_mlta' => $data['mrta_mlta'] ?? null,
            'special_conditions' => $data['special_conditions'] ?? null,
            'approval_deviation_percentage' => $deviation,
        ]);

        return redirect()->route('loans.approval-analysis')->with('success', 'Approval analysis updated.');
    }

    // Render disbursement rows for approved submissions (per loan_id).
    public function disbursement()
    {
        // Disbursement rows are also tracked directly in loans.
        $approvedSubmissions = $this->scopeLoanSubmissionsForLoanAccess(
            LoanBankSubmission::with(['deal.client', 'deal.preQualification', 'deal.bankSubmissions'])
        )
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();

        return view('loans.disbursement', compact('approvedSubmissions', 'newCaseCounts', 'canManageLoanRecords'));
    }

    // Create/update disbursement details for the selected loan.
    public function updateDisbursement(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        $data = $request->validate([
            'loan_id' => ['required', 'string', 'exists:loans,loan_id'],
            'first_disbursement_date' => ['required', 'date'],
            'full_disbursement_date' => ['required', 'date'],
            'spa_completion_date' => ['required', 'date'],
            'client_notification_date' => ['required', 'date'],
        ]);

        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        $submission->update([
            'first_disbursement_date' => $data['first_disbursement_date'] ?? null,
            'full_disbursement_date' => $data['full_disbursement_date'] ?? null,
            'spa_completion_date' => $data['spa_completion_date'] ?? null,
            'client_notification_date' => $data['client_notification_date'] ?? null,
        ]);

        return redirect()->route('loans.disbursement')->with('success', 'Disbursement details updated.');
    }

    // Synchronize deal pipeline when submission status transitions.
    protected function syncDealPipelineByApprovalStatus(Deal $deal, string $status): void
    {
        if ($status === 'Submitted') {
            $deal->update(['pipeline' => PipelineEnum::LOAN_SUBMITTED->value]);
            return;
        }

        if ($status === 'Approved') {
            $deal->update(['pipeline' => PipelineEnum::LOAN_APPROVED->value]);
        }
    }

    // Compute red-badge counts for "new/empty" rows across the five loan tabs.
    protected function getLoanNewCaseCounts(): array
    {
        $dealQuery = fn() => $this->scopeDealsForLoanAccess(Deal::query());
        $submissionQuery = fn() => $this->scopeLoanSubmissionsForLoanAccess(LoanBankSubmission::query());

        return [
            'borrower_profile' => $dealQuery()->whereIn('pipeline', PipelineEnum::creatableValues())
                ->where(function ($query) {
                    $query->doesntHave('preQualification')
                        ->orWhereHas('preQualification', function ($sub) {
                            $sub->whereNull('existing_loans')
                                ->whereNull('monthly_commitments')
                                ->whereNull('credit_card_limits')
                                ->whereNull('credit_card_utilization')
                                ->whereNull('ccris')
                                ->whereNull('ctos');
                        });
                })
                ->count(),
            'pre_qualification' => $dealQuery()->whereIn('pipeline', PipelineEnum::creatableValues())
                ->doesntHave('preQualification')
                ->count(),
            'bank_submission_tracking' => $dealQuery()->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
            ])
                ->doesntHave('bankSubmissions')
                ->count(),
            'approval_analysis' => $submissionQuery()->where('approval_status', 'Approved')
                ->whereNull('applied_amount')
                ->whereNull('approved_amount')
                ->whereNull('interest_rate')
                ->whereNull('lock_in_period')
                ->whereNull('mrta_mlta')
                ->whereNull('special_conditions')
                ->count(),
            'disbursement' => $submissionQuery()->where('approval_status', 'Approved')
                ->whereNull('first_disbursement_date')
                ->whereNull('full_disbursement_date')
                ->whereNull('spa_completion_date')
                ->whereNull('client_notification_date')
                ->count(),
            'legal' => $dealQuery()->where('pipeline', PipelineEnum::LOAN_APPROVED->value)
                ->doesntHave('legalCase')
                ->count(),
        ];
    }

    // Build unified report payload used by all loan pages.
    protected function buildLoanDetailPayload(Deal $deal): array
    {
        $pre = $deal->preQualification;
        $storedRecommendations = is_array($pre?->recommended_banks) ? $pre->recommended_banks : [];
        $hasStructuredRecommendations = !empty($storedRecommendations)
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
            'unit_number' => $deal->unit_number,
            'selling_price' => $deal->selling_price,
            'created_at' => optional($deal->created_at)->format('Y-m-d'),
            'client' => [
                'client_id' => $deal->client?->client_id,
                'name' => $deal->client?->name,
                'email' => $deal->client?->email,
                'phone' => $deal->client?->phone,
                'age' => $deal->client?->age,
                'ic_passport' => $deal->client?->ic_passport,
                'occupation' => $deal->client?->occupation,
                'company' => $deal->client?->company,
                'monthly_income' => $deal->client?->monthly_income,
                'completeness_rate' => is_null($deal->client?->completeness_rate) ? null : ($deal->client?->completeness_rate . '%'),
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
                fn($loan) => !is_null($loan->approved_bank) || !is_null($loan->applied_amount) || !is_null($loan->approved_amount)
            )->map(fn($loan) => [
                    'loan_id' => $loan->loan_id,
                    'approved_bank' => $loan->approved_bank ?? $loan->bank_name,
                    'applied_amount' => $loan->applied_amount,
                    'approved_amount' => $loan->approved_amount,
                    'interest_rate' => $loan->interest_rate,
                    'lock_in_period' => $loan->lock_in_period,
                ])->values()->all(),
            'disbursements' => $allLoanRows->filter(
                fn($loan) => !is_null($loan->first_disbursement_date) || !is_null($loan->full_disbursement_date) || !is_null($loan->spa_completion_date) || !is_null($loan->client_notification_date)
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

    protected function canManageLoanRecords(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRole(RoleEnum::ADMIN->value) || $user->hasRole(RoleEnum::LOAN_OFFICER->value));
    }

    protected function ensureCanManageLoanRecords(): void
    {
        abort_unless($this->canManageLoanRecords(), 403);
    }

    protected function scopeDealsForLoanAccess(Builder $query): Builder
    {
        $user = auth()->user();
        abort_if(!$user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    protected function scopeLoanSubmissionsForLoanAccess(Builder $query): Builder
    {
        $user = auth()->user();
        abort_if(!$user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->whereHas('deal', function (Builder $dealQuery) use ($user) {
                $dealQuery->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    protected function ensureCanViewDeal(Deal $deal): void
    {
        $isAllowed = $this->scopeDealsForLoanAccess(Deal::query())
            ->whereKey($deal->id)
            ->exists();

        abort_unless($isAllowed, 403);
    }
}
