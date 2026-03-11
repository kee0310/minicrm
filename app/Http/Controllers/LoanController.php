<?php

namespace App\Http\Controllers;

use App\Enums\BankEnum;
use App\Enums\LoanApprovalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use App\Models\User;
use App\Query\Loan\ApprovalAnalysisQuery;
use App\Query\Loan\BankSubmissionQuery;
use App\Query\Loan\BorrowerProfileQuery;
use App\Query\Loan\DisbursementQuery;
use App\Query\Loan\PreQualificationQuery;
use App\Services\LoanAccessService;
use App\Services\LoanNotificationService;
use App\Services\LoanService;
use App\Services\OfficerDirectoryService;
use App\Services\OfficerAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService,
        private LoanAccessService $loanAccessService,
        private OfficerDirectoryService $officerDirectory,
        private OfficerAssignmentService $officerAssignment
    ) {}

    // Borrower profile grid + financial/risk updates.
    public function borrowerProfile(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLoanRecords($authUser);
        $query = $this->loanAccessService->scopeDealsForLoanAccess(
            Deal::with([
                'client:id,name,email,phone,age,ic_passport,occupation,company,working_years,monthly_income,fixed_income',
                'preQualification:id,deal_id,existing_loans,monthly_commitments,credit_card_limits,credit_card_utilization,ccris,ctos,risk_grade',
                'loanOfficer:id,name',
            ]),
            $authUser
        );

        BorrowerProfileQuery::build($query, $request, $canManageLoanRecords, $authUser);

        $deals = $query->paginate(10)->withQueryString();
        [$loanOfficers, $currentLoanOfficerId] = $this->officerDirectory
            ->listAndCurrent($authUser, RoleEnum::LOAN_OFFICER->value);

        return view('loans.borrower-profile', compact('deals', 'canManageLoanRecords', 'loanOfficers', 'currentLoanOfficerId'));
    }

    public function notifications()
    {
        /** @var User|null $user */
        $user = auth()->user();
        $badges = app(LoanNotificationService::class)->forUser($user);

        return response()->json(['data' => $badges]);
    }

    // Return one normalized loan detail payload for report modal.
    public function loanDetail(Deal $deal)
    {
        $this->loanAccessService->ensureCanViewDeal($deal, auth()->user());
        $deal->loadMissing(['client', 'preQualification', 'bankSubmissions', 'salesperson', 'leader', 'loanOfficer', 'legalOfficer', 'legalCase']);

        return response()->json([
            'data' => $this->loanService->buildLoanDetailPayload($deal),
        ]);
    }

    // Return loan detail payload by loan_id (for pages that are keyed by loan rows).
    public function loanDetailByLoanId(string $loanId)
    {
        $submission = LoanBankSubmission::with(['deal.client', 'deal.preQualification', 'deal.bankSubmissions', 'deal.salesperson', 'deal.leader', 'deal.loanOfficer', 'deal.legalOfficer', 'deal.legalCase'])
            ->where('loan_id', $loanId)
            ->firstOrFail();
        $this->loanAccessService->ensureCanViewDeal($submission->deal, auth()->user());

        return response()->json([
            'data' => $this->loanService->buildLoanDetailPayload($submission->deal),
        ]);
    }

    // Validate and persist borrower financial metrics, then refresh risk grade.
    public function updateBorrowerProfile(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);
        $this->loanAccessService->ensureCanViewDeal($deal, $authUser);

        if ($this->officerAssignment->isCaseTaken(
            $deal,
            $authUser,
            RoleEnum::LOAN_OFFICER->value,
            'loan_officer_id'
        )) {
            return $this->jsonOrRedirect($request, 'Case has been taken.', 409, 'warning');
        }

        $loanOfficerIds = $this->officerDirectory->idsForRole(RoleEnum::LOAN_OFFICER->value);

        $data = $request->validate([
            'existing_loans' => ['nullable', 'numeric', 'min:0'],
            'monthly_commitments' => ['nullable', 'numeric', 'min:0'],
            'credit_card_limits' => ['nullable', 'numeric', 'min:0'],
            'credit_card_utilization' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ccris' => ['nullable', 'string', 'max:500'],
            'ctos' => ['nullable', 'string', 'max:500'],
            'assign_to' => ['nullable', 'integer', Rule::in($loanOfficerIds)],
        ]);

        $successMessage = $this->loanService->updateBorrowerProfile($deal, $data, $authUser);

        return $this->jsonOrRedirect($request, $successMessage);
    }

    // Render pre-qualification table with deal, client risk, and bank options.
    public function preQualification(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLoanRecords($authUser);
        $query = $this->loanAccessService->scopeDealsForRestrictedLoanAccess(
            Deal::with([
                'preQualification:id,deal_id,risk_grade,recommended_banks,pre_qualification_date',
                'client:id,name',
                'loanOfficer:id,name',
            ]),
            $authUser
        )
            ->whereIn('pipeline', PipelineEnum::values());

        PreQualificationQuery::build($query, $request, $canManageLoanRecords);

        $deals = $query->paginate(10)->withQueryString();
        $bankOptions = BankEnum::values();
        return view('loans.pre-qualification', compact('deals', 'bankOptions', 'canManageLoanRecords'));
    }

    // Save three-slot bank recommendations and pre-qualification date for a deal.
    public function updatePreQualification(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);
        $this->loanAccessService->ensureCanViewRestrictedDeal($deal, $authUser);

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

        $successMessage = $this->loanService->updatePreQualification($deal, $data);

        return redirect()->back()->with('success', $successMessage);
    }

    // Render bank submission tracking with submission status options.
    public function bankSubmissionTracking(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLoanRecords($authUser);
        $query = $this->loanAccessService->scopeDealsForRestrictedLoanAccess(
            Deal::with([
                'bankSubmissions:loan_id,deal_id,bank_name,banker_contact,submission_date,document_completeness_score,approval_status,expected_approval_date,file_completeness_percentage,updated_at',
                'client:id,name',
                'loanOfficer:id,name',
            ]),
            $authUser
        )->whereIn('pipeline', BankSubmissionQuery::pipelineStages());

        $summaryBase = $this->loanAccessService->scopeDealsForRestrictedLoanAccess(
            Deal::query()->whereIn('pipeline', BankSubmissionQuery::pipelineStages()),
            $authUser
        );
        $summary = BankSubmissionQuery::summary($summaryBase);
        BankSubmissionQuery::build($query, $request, $canManageLoanRecords);

        $deals = $query->paginate(10)->withQueryString();

        $bankOptions = BankEnum::values();
        $statusOptions = LoanApprovalStatusEnum::values();
        $eligibleDeals = $this->loanService->eligibleDealsForBankSubmission(
            $canManageLoanRecords,
            $this->loanAccessService->scopeDealsForRestrictedLoanAccess(Deal::query(), $authUser)
        );

        return view('loans.bank-submission-tracking', compact('deals', 'bankOptions', 'statusOptions', 'canManageLoanRecords', 'eligibleDeals', 'summary'));
    }

    // Create a bank submission row and propagate workflow side effects.
    public function storeBankSubmission(Request $request)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);

        $data = $request->validate([
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['required', 'string', 'max:255'],
            'submission_date' => ['required', 'date'],
            'document_completeness_score' => ['required', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', Rule::in(LoanApprovalStatusEnum::values())],
            'expected_approval_date' => ['required', 'date'],
            'file_completeness_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $deal = $this->loanAccessService->scopeDealsForRestrictedLoanAccess(Deal::query(), $authUser)
            ->whereKey($data['deal_id'])
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
            ])
            ->firstOrFail();
        $submission = $this->loanService->createBankSubmission($deal, $data);

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);
        $successMessage = "Bank submission for deal {$dealCode} added successfully.";

        return $this->jsonOrRedirect(
            $request,
            $successMessage,
            200,
            'success',
            route('loans.bank-submission-tracking')
        );
    }

    // Update an existing bank submission and re-apply workflow side effects.
    public function updateBankSubmission(Request $request, LoanBankSubmission $submission)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);

        $data = $request->validate([
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'bank_name' => ['required', Rule::in(BankEnum::values())],
            'banker_contact' => ['required', 'string', 'max:255'],
            'submission_date' => ['nullable', 'date'],
            'document_completeness_score' => ['required', 'integer', 'min:1', 'max:5'],
            'approval_status' => ['required', 'string', Rule::in(LoanApprovalStatusEnum::values())],
            'expected_approval_date' => ['required', 'date'],
            'file_completeness_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $deal = $this->loanAccessService->scopeDealsForRestrictedLoanAccess(Deal::query(), $authUser)
            ->whereKey($data['deal_id'])
            ->firstOrFail();

        if ((int) $submission->deal_id !== (int) $deal->id) {
            abort(422, 'Invalid deal selected.');
        }

        $this->loanService->updateBankSubmission($submission, $deal, $data);

        $dealCode = $submission->deal?->deal_id ?? ('#' . $submission->deal_id);
        $successMessage = "Bank submission for deal {$dealCode} updated successfully.";

        return $this->jsonOrRedirect($request, $successMessage);
    }

    // Render approval analysis rows for approved submissions (per loan_id).
    public function approvalAnalysis(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLoanRecords($authUser);
        // Approval Analysis rows are driven directly from approved loans.
        $query = $this->loanAccessService->scopeLoanSubmissionsForRestrictedLoanAccess(
            LoanBankSubmission::with([
                'deal:id,deal_id,project_name,lead_id,loan_officer_id',
                'deal.client:id,name',
                'deal.loanOfficer:id,name',
            ]),
            $authUser
        )
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value);

        ApprovalAnalysisQuery::build($query, $request, $canManageLoanRecords);

        $approvedSubmissions = $query->paginate(10)->withQueryString();
        $bankOptions = BankEnum::values();
        $bankApprovalRates = $this->loanService->buildBankApprovalRates(
            $this->loanAccessService->scopeLoanSubmissionsForRestrictedLoanAccess(LoanBankSubmission::query(), $authUser),
            $bankOptions
        );

        return view('loans.approval-analysis', compact('approvedSubmissions', 'bankOptions', 'canManageLoanRecords', 'bankApprovalRates'));
    }

    // Create/update approval analysis details for the selected loan.
    public function storeApprovalAnalysis(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);
        $this->loanAccessService->ensureCanViewRestrictedDeal($deal, $authUser);

        $data = $this->validateApprovalAnalysis($request);
        $this->loanService->persistApprovalAnalysis($deal, $data);

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);

        return redirect()->route('loans.approval-analysis')->with('success', "Approval analysis for deal {$dealCode} added successfully.");
    }

    // Update approval analysis details for the selected loan.
    public function updateApprovalAnalysis(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);
        $this->loanAccessService->ensureCanViewRestrictedDeal($deal, $authUser);

        $data = $this->validateApprovalAnalysis($request);
        $this->loanService->persistApprovalAnalysis($deal, $data);

        $dealCode = $deal->deal_id ?? ('#' . $deal->id);

        return redirect()->back()->with('success', "Approval analysis for deal {$dealCode} updated successfully.");
    }

    // Render disbursement rows for approved submissions (per loan_id).
    public function disbursement(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLoanRecords($authUser);
        // Disbursement rows are also tracked directly in loans.
        $query = $this->loanAccessService->scopeLoanSubmissionsForRestrictedLoanAccess(
            LoanBankSubmission::with([
                'deal:id,deal_id,project_name,lead_id,loan_officer_id',
                'deal.client:id,name',
                'deal.loanOfficer:id,name',
            ]),
            $authUser
        )
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value);

        DisbursementQuery::build($query, $request, $canManageLoanRecords);

        $approvedSubmissions = $query->paginate(10)->withQueryString();

        return view('loans.disbursement', compact('approvedSubmissions', 'canManageLoanRecords'));
    }

    // Create/update disbursement details for the selected loan.
    public function updateDisbursement(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLoanRecords($authUser);
        $this->loanAccessService->ensureCanViewRestrictedDeal($deal, $authUser);

        $data = $request->validate([
            'loan_id' => ['required', 'string', 'exists:loans,loan_id'],
            'first_disbursement_date' => ['nullable', 'date'],
            'full_disbursement_date' => ['nullable', 'date'],
            'spa_completion_date' => ['nullable', 'date'],
            'client_notification_date' => ['nullable', 'date'],
        ]);

        $successMessage = $this->loanService->updateDisbursement($deal, $data);

        return redirect()->back()->with('success', $successMessage);
    }

    protected function validateApprovalAnalysis(Request $request): array
    {
        return $request->validate([
            'loan_id' => ['required', 'string', 'exists:loans,loan_id'],
            'approved_bank' => ['required', Rule::in(BankEnum::values())],
            'applied_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lock_in_period' => ['required', 'string', 'max:255'],
            'mrta_mlta' => ['required', 'string', 'max:255'],
            'special_conditions' => ['nullable', 'string'],
        ]);
    }
}
