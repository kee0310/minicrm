<?php

namespace App\Query\Loan;

use App\Support\Query\CompletionFilter;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApprovalAnalysisQuery
{
    public static function build(Builder $query, Request $request, bool $canManageLoanRecords): Builder
    {
        $query->select([
            'loans.loan_id',
            'loans.deal_id',
            'loans.bank_name',
            'loans.banker_contact',
            'loans.approved_bank',
            'loans.applied_amount',
            'loans.approved_amount',
            'loans.interest_rate',
            'loans.lock_in_period',
            'loans.mrta_mlta',
            'loans.special_conditions',
            'loans.updated_at',
        ])
            ->leftJoin('deals', 'deals.id', '=', 'loans.deal_id')
            ->leftJoin('leads', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('users as loan_officers', 'loan_officers.id', '=', 'deals.loan_officer_id')
            ->leftJoin('deal_pipelines', 'deal_pipelines.deal_id', '=', 'loans.deal_id')
            ->selectRaw(
                'CASE
                    WHEN loans.applied_amount IS NULL OR loans.applied_amount = 0 OR loans.approved_amount IS NULL THEN NULL
                    ELSE ROUND(((loans.approved_amount - loans.applied_amount) / loans.applied_amount) * 100, 2)
                END as approval_deviation_percentage'
            )
            ->addSelect([
                'deals.deal_id as deal_code',
                'deals.project_name as deal_project_name',
                'leads.name as deal_client_name',
                'deal_pipelines.loan_approved_date as deal_loan_approved_date',
                'loan_officers.name as deal_loan_officer_name',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyBankFilter($query, $request->input('bank'));
        CompletionFilter::apply(
            $query,
            $request->input('completion'),
            function (Builder $completionQuery) {
                $completionQuery->whereNull('applied_amount')
                    ->whereNull('approved_amount')
                    ->whereNull('interest_rate')
                    ->whereNull('lock_in_period')
                    ->whereNull('mrta_mlta')
                    ->whereNull('special_conditions');
            },
            function (Builder $completionQuery) {
                $completionQuery->where(function (Builder $query) {
                    $query->whereNotNull('applied_amount')
                        ->orWhereNotNull('approved_amount')
                        ->orWhereNotNull('interest_rate')
                        ->orWhereNotNull('lock_in_period')
                        ->orWhereNotNull('mrta_mlta')
                        ->orWhereNotNull('special_conditions');
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
                $dealQuery->where('deals.deal_id', 'like', "%{$search}%")
                    ->orWhere('deals.project_name', 'like', "%{$search}%")
                    ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    });
            })->orWhere('loan_officers.name', 'like', "%{$search}%")
                ->orWhere('loans.banker_contact', 'like', "%{$search}%");
        });
    }

    protected static function applyBankFilter(Builder $query, ?string $bank): void
    {
        if (! $bank) {
            return;
        }

        $query->where('loans.approved_bank', $bank);
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
            (string) ($sortStart + 3) => 'approved_bank',
            (string) ($sortStart + 4) => 'banker_contact',
            (string) ($sortStart + 5) => 'applied_amount',
            (string) ($sortStart + 6) => 'approved_amount',
            (string) ($sortStart + 7) => 'interest_rate',
            (string) ($sortStart + 8) => 'lock_in_period',
            (string) ($sortStart + 9) => 'mrta_mlta',
            (string) ($sortStart + 10) => 'special_conditions',
        ];

        if ($sortBy === (string) ($sortStart + 11)) {
            $query->orderByRaw(
                'CASE
                    WHEN loans.applied_amount IS NULL OR loans.applied_amount = 0 OR loans.approved_amount IS NULL THEN NULL
                    ELSE ROUND(((loans.approved_amount - loans.applied_amount) / loans.applied_amount) * 100, 2)
                END '.$sortDir
            );

            return;
        }

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw(
            'CASE WHEN applied_amount IS NULL
                    AND approved_amount IS NULL
                    AND interest_rate IS NULL
                    AND lock_in_period IS NULL
                    AND mrta_mlta IS NULL
                    AND special_conditions IS NULL
                THEN 0 ELSE 1 END'
        )->latest('deal_loan_approved_date');
    }
}
