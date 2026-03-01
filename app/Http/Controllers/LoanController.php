<?php

namespace App\Http\Controllers;

use App\Enums\BankEnum;
use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    // Borrower profile grid + financial/risk updates.
    public function borrowerProfile()
    {
        $deals = Deal::with(['client', 'preQualification'])
            ->latest()
            ->get();
        $newCaseCounts = $this->getLoanNewCaseCounts();

        return view('loans.borrower-profile', compact('deals', 'newCaseCounts'));
    }

    // Validate and persist borrower financial metrics, then refresh risk grade.
    public function updateBorrowerProfile(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'existing_loans' => ['nullable', 'numeric', 'min:0'],
            'monthly_commitments' => ['nullable', 'numeric', 'min:0'],
            'credit_card_limits' => ['nullable', 'numeric', 'min:0'],
            'credit_card_utilization' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ccris' => ['nullable', 'string', 'max:500'],
            'ctos' => ['nullable', 'string', 'max:500'],
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
        $deals = Deal::with(['preQualification', 'client'])
            ->whereIn('pipeline', PipelineEnum::creatableValues())
            ->latest()
            ->get();
        $bankOptions = BankEnum::values();
        $newCaseCounts = $this->getLoanNewCaseCounts();

        return view('loans.pre-qualification', compact('deals', 'bankOptions', 'newCaseCounts'));
    }

    // Save three-slot bank recommendations and pre-qualification date for a deal.
    public function updatePreQualification(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'pre_qualification_date' => ['nullable', 'date'],
            'recommended_bank_1' => ['nullable', Rule::in(BankEnum::values())],
            'recommended_bank_2' => ['nullable', Rule::in(BankEnum::values())],
            'recommended_bank_3' => ['nullable', Rule::in(BankEnum::values())],
            'approval_probability_1' => ['nullable', 'integer', 'min:0', 'max:100'],
            'approval_probability_2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'approval_probability_3' => ['nullable', 'integer', 'min:0', 'max:100'],
            'loan_margin_1' => ['nullable', 'integer', 'in:70,80,90'],
            'loan_margin_2' => ['nullable', 'integer', 'in:70,80,90'],
            'loan_margin_3' => ['nullable', 'integer', 'in:70,80,90'],
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
        $deals = Deal::with(['bankSubmissions', 'client', 'preQualification'])
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
            ])
            ->latest()
            ->get();

        $bankOptions = BankEnum::values();
        $statusOptions = ['Prepared', 'Submitted', 'In Review', 'Approved', 'Rejected'];
        $newCaseCounts = $this->getLoanNewCaseCounts();

        return view('loans.bank-submission-tracking', compact('deals', 'bankOptions', 'statusOptions', 'newCaseCounts'));
    }

    // Create a bank submission row and propagate workflow side effects.
    public function storeBankSubmission(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['nullable', 'string', 'max:255'],
            'submission_date' => ['nullable', 'date'],
            'document_completeness_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', 'in:Prepared,Submitted,In Review,Approved,Rejected'],
            'expected_approval_date' => ['nullable', 'date'],
            'file_completeness_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $submission = $deal->bankSubmissions()->create($data);
        $this->syncDealPipelineByApprovalStatus($deal, $submission->approval_status);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission added.');
    }

    // Update an existing bank submission and re-apply workflow side effects.
    public function updateBankSubmission(Request $request, LoanBankSubmission $submission)
    {
        $data = $request->validate([
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['nullable', 'string', 'max:255'],
            'submission_date' => ['nullable', 'date'],
            'document_completeness_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', 'in:Prepared,Submitted,In Review,Approved,Rejected'],
            'expected_approval_date' => ['nullable', 'date'],
            'file_completeness_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $submission->update($data);
        $this->syncDealPipelineByApprovalStatus($submission->deal, $submission->approval_status);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission updated.');
    }

    // Render approval analysis rows for approved submissions (per loan_id).
    public function approvalAnalysis()
    {
        // Approval Analysis rows are driven directly from approved loans.
        $approvedSubmissions = LoanBankSubmission::with(['deal'])
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();
        $bankOptions = BankEnum::values();
        $newCaseCounts = $this->getLoanNewCaseCounts();

        return view('loans.approval-analysis', compact('approvedSubmissions', 'bankOptions', 'newCaseCounts'));
    }

    // Create/update approval analysis details for the selected loan.
    public function storeApprovalAnalysis(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loans,loan_id'],
            'approved_bank' => ['nullable', Rule::in(BankEnum::values())],
            'applied_amount' => ['nullable', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lock_in_period' => ['nullable', 'string', 'max:255'],
            'mrta_mlta' => ['nullable', 'string', 'max:255'],
            'special_conditions' => ['nullable', 'string'],
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
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loans,loan_id'],
            'approved_bank' => ['nullable', Rule::in(BankEnum::values())],
            'applied_amount' => ['nullable', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lock_in_period' => ['nullable', 'string', 'max:255'],
            'mrta_mlta' => ['nullable', 'string', 'max:255'],
            'special_conditions' => ['nullable', 'string'],
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
        $approvedSubmissions = LoanBankSubmission::with(['deal'])
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();
        $newCaseCounts = $this->getLoanNewCaseCounts();

        return view('loans.disbursement', compact('approvedSubmissions', 'newCaseCounts'));
    }

    // Create/update disbursement details for the selected loan.
    public function updateDisbursement(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loans,loan_id'],
            'first_disbursement_date' => ['nullable', 'date'],
            'full_disbursement_date' => ['nullable', 'date'],
            'spa_completion_date' => ['nullable', 'date'],
            'client_notification_date' => ['nullable', 'date'],
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
        return [
            'borrower_profile' => Deal::whereIn('pipeline', PipelineEnum::creatableValues())
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
            'pre_qualification' => Deal::whereIn('pipeline', PipelineEnum::creatableValues())
                ->doesntHave('preQualification')
                ->count(),
            'bank_submission_tracking' => Deal::whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
            ])
                ->doesntHave('bankSubmissions')
                ->count(),
            'approval_analysis' => LoanBankSubmission::where('approval_status', 'Approved')
                ->whereNull('applied_amount')
                ->whereNull('approved_amount')
                ->whereNull('interest_rate')
                ->whereNull('lock_in_period')
                ->whereNull('mrta_mlta')
                ->whereNull('special_conditions')
                ->count(),
            'disbursement' => LoanBankSubmission::where('approval_status', 'Approved')
                ->whereNull('first_disbursement_date')
                ->whereNull('full_disbursement_date')
                ->whereNull('spa_completion_date')
                ->whereNull('client_notification_date')
                ->count(),
        ];
    }
}
