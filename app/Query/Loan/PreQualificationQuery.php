<?php

namespace App\Query\Loan;

use App\Support\Query\CompletionFilter;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PreQualificationQuery
{
    public static function build(Builder $query, Request $request, bool $canManageLoanRecords): Builder
    {
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
            'loan_pre_qualifications.recommended_banks as preq_recommended_banks',
            'loan_pre_qualifications.pre_qualification_date as preq_date',
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
                            $preQuery->whereNull('pre_qualification_date')
                                ->where(function (Builder $bankQuery) {
                                    $bankQuery->whereNull('recommended_banks')
                                        ->orWhereJsonLength('recommended_banks', 0);
                                });
                        });
                });
            },
            function (Builder $completionQuery) {
                $completionQuery->whereHas('preQualification', function (Builder $preQuery) {
                    $preQuery->whereNotNull('pre_qualification_date')
                        ->orWhereJsonLength('recommended_banks', '>', 0);
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
            $dealQuery->where('deals.deal_id', 'like', "%{$search}%")
                ->orWhere('deals.project_name', 'like', "%{$search}%")
                ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('loan_officers.name', 'like', "%{$search}%");
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
            (string) ($sortStart + 4) => 'preq_recommended_banks',
            (string) ($sortStart + 5) => 'preq_recommended_banks',
            (string) ($sortStart + 6) => 'preq_recommended_banks',
            (string) ($sortStart + 7) => 'preq_date',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw(
            "CASE WHEN preq_date IS NULL
                    AND (preq_recommended_banks IS NULL OR preq_recommended_banks = '[]')
                THEN 0 ELSE 1 END"
        )->latest('preq_date');
    }
}
