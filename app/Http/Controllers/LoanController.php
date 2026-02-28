<?php

namespace App\Http\Controllers;

use App\Enums\BankEnum;
use App\Enums\PipelineEnum;
use App\Models\Client;
use App\Models\Deal;
use App\Models\LoanApprovalAnalysis;
use App\Models\LoanBankSubmission;
use App\Models\LoanDisbursement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function borrowerProfile()
    {
        $clients = Client::with([
            'financialCondition',
            'deals' => fn($query) => $query->latest(),
        ])
            ->latest()
            ->get();

        return view('loans.borrower-profile', compact('clients'));
    }

    public function updateBorrowerProfile(Request $request, Client $client)
    {
        $data = $request->validate([
            'existing_loans' => ['nullable', 'numeric', 'min:0'],
            'monthly_commitments' => ['nullable', 'numeric', 'min:0'],
            'credit_card_limits' => ['nullable', 'numeric', 'min:0'],
            'credit_card_utilization' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ccris' => ['nullable', 'string', 'max:500'],
            'ctos' => ['nullable', 'string', 'max:500'],
        ]);

        $financial = $client->financialCondition()->firstOrCreate([]);
        $financial->fill($data);
        $financial->risk_grade = $financial->riskGrade();
        $financial->save();

        return redirect()->route('loans.borrower-profile')->with('success', 'Borrower profile updated.');
    }

    public function preQualification()
    {
        $deals = Deal::with(['preQualification', 'client.financialCondition'])
            ->whereIn('pipeline', PipelineEnum::creatableValues())
            ->latest()
            ->get();
        $bankOptions = BankEnum::values();

        return view('loans.pre-qualification', compact('deals', 'bankOptions'));
    }

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

    public function bankSubmissionTracking()
    {
        $deals = Deal::with(['bankSubmissions', 'client.financialCondition', 'preQualification'])
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
            ])
            ->latest()
            ->get();

        $bankOptions = BankEnum::values();
        $statusOptions = ['Prepared', 'Submitted', 'In Review', 'Approved', 'Rejected'];

        return view('loans.bank-submission-tracking', compact('deals', 'bankOptions', 'statusOptions'));
    }

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
        $this->ensureDependentLoanRows($submission);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission added.');
    }

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
        $this->ensureDependentLoanRows($submission);

        return redirect()->route('loans.bank-submission-tracking')->with('success', 'Bank submission updated.');
    }

    public function approvalAnalysis()
    {
        $approvedSubmissions = LoanBankSubmission::with(['deal', 'approvalAnalysis'])
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();
        $bankOptions = BankEnum::values();

        return view('loans.approval-analysis', compact('approvedSubmissions', 'bankOptions'));
    }

    public function storeApprovalAnalysis(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loan_bank_submissions,loan_id'],
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

        LoanApprovalAnalysis::updateOrCreate(
            ['loan_id' => $submission->loan_id],
            [
                'deal_id' => $deal->id,
                'loan_id' => $submission->loan_id,
                'approved_bank' => $data['approved_bank'] ?? null,
                'applied_amount' => $data['applied_amount'] ?? null,
                'approved_amount' => $data['approved_amount'] ?? null,
                'interest_rate' => $data['interest_rate'] ?? null,
                'lock_in_period' => $data['lock_in_period'] ?? null,
                'mrta_mlta' => $data['mrta_mlta'] ?? null,
                'special_conditions' => $data['special_conditions'] ?? null,
                'approval_deviation_percentage' => $deviation,
            ]
        );

        return redirect()->route('loans.approval-analysis')->with('success', 'Approval analysis added.');
    }

    public function updateApprovalAnalysis(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loan_bank_submissions,loan_id'],
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

        LoanApprovalAnalysis::updateOrCreate(
            ['loan_id' => $submission->loan_id],
            [
                'deal_id' => $deal->id,
                'loan_id' => $submission->loan_id,
                'approved_bank' => $data['approved_bank'] ?? null,
                'applied_amount' => $data['applied_amount'] ?? null,
                'approved_amount' => $data['approved_amount'] ?? null,
                'interest_rate' => $data['interest_rate'] ?? null,
                'lock_in_period' => $data['lock_in_period'] ?? null,
                'mrta_mlta' => $data['mrta_mlta'] ?? null,
                'special_conditions' => $data['special_conditions'] ?? null,
                'approval_deviation_percentage' => $deviation,
            ]
        );

        return redirect()->route('loans.approval-analysis')->with('success', 'Approval analysis updated.');
    }

    public function disbursement()
    {
        $approvedSubmissions = LoanBankSubmission::with(['deal', 'disbursement'])
            ->where('approval_status', 'Approved')
            ->latest('loan_id')
            ->get();

        return view('loans.disbursement', compact('approvedSubmissions'));
    }

    public function updateDisbursement(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'integer', 'exists:loan_bank_submissions,loan_id'],
            'first_disbursement_date' => ['nullable', 'date'],
            'full_disbursement_date' => ['nullable', 'date'],
            'spa_completion_date' => ['nullable', 'date'],
            'client_notification_date' => ['nullable', 'date'],
        ]);

        $submission = LoanBankSubmission::where('deal_id', $deal->id)
            ->where('loan_id', $data['loan_id'])
            ->firstOrFail();

        LoanDisbursement::updateOrCreate(
            ['loan_id' => $submission->loan_id],
            [
                'deal_id' => $deal->id,
                'loan_id' => $submission->loan_id,
                'first_disbursement_date' => $data['first_disbursement_date'] ?? null,
                'full_disbursement_date' => $data['full_disbursement_date'] ?? null,
                'spa_completion_date' => $data['spa_completion_date'] ?? null,
                'client_notification_date' => $data['client_notification_date'] ?? null,
            ]
        );

        return redirect()->route('loans.disbursement')->with('success', 'Disbursement details updated.');
    }

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

    protected function ensureDependentLoanRows(LoanBankSubmission $submission): void
    {
        if ($submission->approval_status !== 'Approved') {
            return;
        }

        LoanApprovalAnalysis::firstOrCreate(
            ['loan_id' => $submission->loan_id],
            [
                'deal_id' => $submission->deal_id,
                'loan_id' => $submission->loan_id,
                'approved_bank' => $submission->bank_name,
            ]
        );

        LoanDisbursement::firstOrCreate(
            ['loan_id' => $submission->loan_id],
            [
                'deal_id' => $submission->deal_id,
                'loan_id' => $submission->loan_id,
            ]
        );
    }
}
