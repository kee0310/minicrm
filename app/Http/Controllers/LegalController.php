<?php

namespace App\Http\Controllers;

use App\Enums\LegalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Query\Legal\LegalIndexQuery;
use App\Services\LegalService;
use App\Services\LoanAccessService;
use App\Services\OfficerAssignmentService;
use App\Services\OfficerDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalController extends Controller
{
    public function __construct(
        private LoanAccessService $loanAccessService,
        private LegalService $legalService,
        private OfficerDirectoryService $officerDirectory,
        private OfficerAssignmentService $officerAssignment
    ) {}

    // Render legal table for loan approved deals.
    public function index(Request $request)
    {
        $authUser = $request->user();
        $canManageLoanRecords = $this->loanAccessService->canManageLegalRecords($authUser);
        $query = $this->loanAccessService->scopeDealsForLoanAccess(
            Deal::with(['client', 'legalCase', 'legalOfficer']),
            $authUser
        )->whereIn('pipeline', [
            PipelineEnum::LOAN_APPROVED->value,
            PipelineEnum::LEGAL_PROCESSING->value,
            PipelineEnum::COMPLETED->value,
        ]);

        LegalIndexQuery::build($query, $request, $authUser, $canManageLoanRecords);
        $summary = LegalIndexQuery::summary(clone $query);
        $deals = $query->paginate(10)->withQueryString();

        $statusOptions = LegalStatusEnum::values();
        [$legalOfficers, $currentLegalOfficerId] = $this->officerDirectory
            ->listAndCurrent($authUser, RoleEnum::LEGAL_OFFICER->value);

        return view('legals.index', compact('deals', 'statusOptions', 'canManageLoanRecords', 'legalOfficers', 'currentLegalOfficerId', 'summary'));
    }

    // Create/update legal details for a deal in legal workflow.
    public function update(Request $request, Deal $deal)
    {
        $authUser = $request->user();
        $this->loanAccessService->ensureCanManageLegalRecords($authUser);
        $this->loanAccessService->ensureCanViewDeal($deal, $authUser);
        $legalOfficerIds = $this->officerDirectory->idsForRole(RoleEnum::LEGAL_OFFICER->value);

        if ($this->officerAssignment->isCaseTaken(
            $deal,
            $authUser,
            RoleEnum::LEGAL_OFFICER->value,
            'legal_officer_id'
        )) {
            return $this->jsonOrRedirect($request, 'Case has been taken.', 409, 'warning');
        }

        abort_unless(
            in_array($deal->pipeline?->value, [
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
            ], true),
            422,
            'Only loan approved/legal processing/completed deals can be updated in legal.'
        );

        $data = $request->validate([
            'status' => ['nullable', 'string', Rule::in(LegalStatusEnum::values())],
            'lawyer_firm' => ['nullable', 'string', 'max:255'],
            'spa_date' => ['nullable', 'date'],
            'loan_agreement_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date'],
            'stamp_duty' => ['nullable', 'boolean'],
            'assign_to' => ['nullable', 'integer', Rule::in($legalOfficerIds)],
        ]);

        $this->legalService->updateLegalCase($deal, $data, $authUser);
        $dealCode = $deal->deal_id ?? ('#'.$deal->id);
        $successMessage = "Legal case for deal {$dealCode} updated successfully.";

        return $this->jsonOrRedirect($request, $successMessage);
    }
}
