<?php

namespace App\Query\Loan;

use App\Support\Query\CompletionFilter;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DisbursementQuery
{
    public static function build(Builder $query, Request $request, bool $canManageLoanRecords): Builder
    {
        $query->select([
            'loans.loan_id',
            'loans.deal_id',
            'loans.first_disbursement_date',
            'loans.full_disbursement_date',
            'loans.spa_completion_date',
            'loans.client_notification_date',
            'loans.updated_at',
        ])
            ->leftJoin('deals', 'deals.id', '=', 'loans.deal_id')
            ->leftJoin('leads', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('users as loan_officers', 'loan_officers.id', '=', 'deals.loan_officer_id')
            ->leftJoin('deal_pipelines', 'deal_pipelines.deal_id', '=', 'loans.deal_id')
            ->addSelect([
                'deals.deal_id as deal_code',
                'deals.project_name as deal_project_name',
                'leads.name as deal_client_name',
                'deal_pipelines.loan_approved_date as deal_loan_approved_date',
                'loan_officers.name as deal_loan_officer_name',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        CompletionFilter::apply(
            $query,
            $request->input('completion'),
            function (Builder $completionQuery) {
                $completionQuery->whereNull('first_disbursement_date')
                    ->whereNull('full_disbursement_date')
                    ->whereNull('spa_completion_date')
                    ->whereNull('client_notification_date');
            },
            function (Builder $completionQuery) {
                $completionQuery->where(function (Builder $query) {
                    $query->whereNotNull('first_disbursement_date')
                        ->orWhereNotNull('full_disbursement_date')
                        ->orWhereNotNull('spa_completion_date')
                        ->orWhereNotNull('client_notification_date');
                });
            }
        );
        self::applySorting($query, $request, $canManageLoanRecords);

        return $query;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $submissionQuery) use ($search) {
            $submissionQuery->whereHas('deal', function (Builder $dealQuery) use ($search) {
                $dealQuery->where('deal_id', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    });
            });
        });
    }

    protected static function applySorting(Builder $query, Request $request, bool $canManageLoanRecords): void
    {
        $sortBy = (string) $request->input('sort_by', '');
        $sortDir = strtolower((string) $request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';
        $sortStart = $canManageLoanRecords ? 1 : 0;
        $sortMap = [
            (string) $sortStart => 'deal_code',
            (string) ($sortStart + 1) => 'deal_client_name',
            (string) ($sortStart + 2) => 'deal_loan_officer_name',
            (string) ($sortStart + 3) => 'first_disbursement_date',
            (string) ($sortStart + 4) => 'full_disbursement_date',
            (string) ($sortStart + 5) => 'spa_completion_date',
            (string) ($sortStart + 6) => 'client_notification_date',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw(
            'CASE WHEN first_disbursement_date IS NULL
                    AND full_disbursement_date IS NULL
                    AND spa_completion_date IS NULL
                    AND client_notification_date IS NULL
                THEN 0 ELSE 1 END'
        )->latest('deal_loan_approved_date');
    }
}
