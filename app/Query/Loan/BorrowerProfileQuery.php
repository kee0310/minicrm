<?php

namespace App\Query\Loan;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\Query\CompletionFilter;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BorrowerProfileQuery
{
    public static function build(
        Builder $query,
        Request $request,
        bool $canManageLoanRecords,
        ?User $user = null
    ): Builder
    {
        if ($user?->hasRole(RoleEnum::LOAN_OFFICER->value) && ! $user->hasRole(RoleEnum::ADMIN->value)) {
            $query->where(function (Builder $loanOfficerQuery) use ($user) {
                $loanOfficerQuery->whereNull('loan_officer_id')
                    ->orWhere('loan_officer_id', $user->id);
            });
        }

        $query->leftJoin('loan_pre_qualifications', 'loan_pre_qualifications.deal_id', '=', 'deals.id')
            ->leftJoin('leads', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('users as loan_officers', 'loan_officers.id', '=', 'deals.loan_officer_id')
            ->leftJoin('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id');
        $query->select([
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
            'loan_pre_qualifications.risk_grade as preq_risk_grade',
            'loan_pre_qualifications.existing_loans as preq_existing_loans',
            'loan_pre_qualifications.monthly_commitments as preq_monthly_commitments',
            'loan_pre_qualifications.credit_card_limits as preq_credit_card_limits',
            'loan_pre_qualifications.credit_card_utilization as preq_credit_card_utilization',
            'loan_pre_qualifications.ccris as preq_ccris',
            'loan_pre_qualifications.ctos as preq_ctos',
        ])
            ->addSelect([
                'leads.name as client_name',
                'loan_officers.name as loan_officer_name',
                'deal_pipelines.loan_submitted_date as deal_loan_submitted_date',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        CompletionFilter::apply(
            $query,
            $request->input('completion'),
            function (Builder $completionQuery) {
                $completionQuery->where(function (Builder $query) {
                    $query->doesntHave('preQualification')
                        ->orWhereHas('preQualification', function (Builder $preQuery) {
                            $preQuery->whereNull('existing_loans')
                                ->whereNull('monthly_commitments')
                                ->whereNull('credit_card_limits')
                                ->whereNull('credit_card_utilization')
                                ->whereNull('ccris')
                                ->whereNull('ctos');
                        });
                });
            },
            function (Builder $completionQuery) {
                $completionQuery->whereHas('preQualification', function (Builder $preQuery) {
                    $preQuery->where(function (Builder $filledQuery) {
                        $filledQuery->whereNotNull('existing_loans')
                            ->orWhereNotNull('monthly_commitments')
                            ->orWhereNotNull('credit_card_limits')
                            ->orWhereNotNull('credit_card_utilization')
                            ->orWhereNotNull('ccris')
                            ->orWhereNotNull('ctos');
                    });
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

        $query->where(function (Builder $dealQuery) use ($search) {
            $dealQuery->where('deal_id', 'like', "%{$search}%")
                ->orWhere('project_name', 'like', "%{$search}%")
                ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                });
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
            (string) ($sortStart + 3) => 'preq_risk_grade',
            (string) ($sortStart + 4) => 'preq_existing_loans',
            (string) ($sortStart + 5) => 'preq_monthly_commitments',
            (string) ($sortStart + 6) => 'preq_credit_card_limits',
            (string) ($sortStart + 7) => 'preq_credit_card_utilization',
            (string) ($sortStart + 8) => 'preq_ccris',
            (string) ($sortStart + 9) => 'preq_ctos',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw('CASE WHEN deals.loan_officer_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw(
                'CASE WHEN preq_existing_loans IS NULL
                    AND preq_monthly_commitments IS NULL
                    AND preq_credit_card_limits IS NULL
                    AND preq_credit_card_utilization IS NULL
                    AND preq_ccris IS NULL
                    AND preq_ctos IS NULL
                THEN 0 ELSE 1 END'
            )->latest('created_at');
    }
}
