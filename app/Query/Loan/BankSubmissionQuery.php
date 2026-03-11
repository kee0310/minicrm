<?php

namespace App\Query\Loan;

use App\Enums\LoanApprovalStatusEnum;
use App\Enums\PipelineEnum;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BankSubmissionQuery
{
    public static function build(Builder $query, Request $request, bool $canManageLoanRecords): Builder
    {
        $query->leftJoin('leads', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('users as loan_officers', 'loan_officers.id', '=', 'deals.loan_officer_id')
            ->leftJoin('loans as latest_loans', function ($join) {
                $join->on('latest_loans.deal_id', '=', 'deals.id')
                    ->whereRaw('latest_loans.updated_at = (SELECT MAX(l2.updated_at) FROM loans l2 WHERE l2.deal_id = deals.id)');
            })
            ->select([
                'deals.id',
                'deals.deal_id',
                'deals.lead_id',
                'deals.project_name',
                'deals.salesperson_id',
                'deals.leader_id',
                'deals.loan_officer_id',
                'deals.pipeline',
                'deals.created_at',
                'deals.updated_at',
            ])
            ->addSelect([
                'leads.name as client_name',
                'latest_loans.bank_name as latest_bank_name',
                'latest_loans.banker_contact as latest_banker_contact',
                'latest_loans.submission_date as latest_submission_date',
                'latest_loans.document_completeness_score as latest_doc_score',
                'latest_loans.approval_status as latest_approval_status',
                'latest_loans.expected_approval_date as latest_expected_approval_date',
                'latest_loans.file_completeness_percentage as latest_file_completeness',
                'loan_officers.name as loan_officer_name',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyStatusFilter($query, $request->input('status'));
        self::applySorting($query, $request, $canManageLoanRecords);

        return $query;
    }

    public static function pipelineStages(): array
    {
        return PipelineEnum::loanStages();
    }

    public static function summary(Builder $summaryBase): array
    {
        $summaryRow = (clone $summaryBase)
            ->leftJoin('loans', 'loans.deal_id', '=', 'deals.id')
            ->selectRaw('COUNT(DISTINCT deals.id) as total')
            ->selectRaw('COUNT(DISTINCT CASE WHEN loans.loan_id IS NULL THEN deals.id END) as new')
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN loans.approval_status = ? THEN deals.id END) as prepared',
                [LoanApprovalStatusEnum::PREPARED->value]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN loans.approval_status = ? THEN deals.id END) as submitted',
                [LoanApprovalStatusEnum::SUBMITTED->value]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN loans.approval_status = ? THEN deals.id END) as in_review',
                [LoanApprovalStatusEnum::IN_REVIEW->value]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN loans.approval_status = ? THEN deals.id END) as approved',
                [LoanApprovalStatusEnum::APPROVED->value]
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN loans.approval_status = ? THEN deals.id END) as rejected',
                [LoanApprovalStatusEnum::REJECTED->value]
            )
            ->first();

        return [
            'total' => (int) ($summaryRow->total ?? 0),
            'new' => (int) ($summaryRow->new ?? 0),
            'prepared' => (int) ($summaryRow->prepared ?? 0),
            'submitted' => (int) ($summaryRow->submitted ?? 0),
            'in_review' => (int) ($summaryRow->in_review ?? 0),
            'approved' => (int) ($summaryRow->approved ?? 0),
            'rejected' => (int) ($summaryRow->rejected ?? 0),
        ];
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $dealQuery) use ($search) {
            $dealQuery->where('deal_id', 'like', "%{$search}%")
                ->orWhere('project_name', 'like', "%{$search}%")
                ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('bankSubmissions', function (Builder $submissionQuery) use ($search) {
                    $submissionQuery->where('bank_name', 'like', "%{$search}%");
                });
        });
    }

    protected static function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        if ($status === 'No Submission') {
            $query->doesntHave('bankSubmissions');

            return;
        }

        $query->whereHas('bankSubmissions', function (Builder $submissionQuery) use ($status) {
            $submissionQuery->where('approval_status', $status);
        });
    }

    protected static function applySorting(Builder $query, Request $request, bool $canManageLoanRecords): void
    {
        $sortBy = (string) $request->input('sort_by', '');
        $sortDir = strtolower((string) $request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';
        $sortStart = $canManageLoanRecords ? 1 : 0;
        $sortMap = [
            (string) $sortStart => 'deals.deal_id',
            (string) ($sortStart + 1) => 'client_name',
            (string) ($sortStart + 2) => 'loan_officer_name',
            (string) ($sortStart + 3) => 'latest_bank_name',
            (string) ($sortStart + 4) => 'latest_banker_contact',
            (string) ($sortStart + 5) => 'latest_submission_date',
            (string) ($sortStart + 6) => 'latest_doc_score',
            (string) ($sortStart + 7) => 'latest_file_completeness',
            (string) ($sortStart + 8) => 'latest_approval_status',
            (string) ($sortStart + 9) => 'latest_expected_approval_date',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw('CASE WHEN latest_loans.submission_date IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('latest_loans.submission_date');
    }
}
