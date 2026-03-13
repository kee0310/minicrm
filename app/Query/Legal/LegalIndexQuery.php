<?php

namespace App\Query\Legal;

use App\Enums\LegalStatusEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LegalIndexQuery
{
    public static function build(
        Builder $query,
        Request $request,
        ?User $user,
        bool $canManageLoanRecords
    ): Builder {
        self::applyLegalOfficerScope($query, $user);
        self::applySelects($query);
        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyStatusFilter($query, $request->input('status'));
        self::applySorting($query, $request, $canManageLoanRecords);

        return $query;
    }

    public static function summary(Builder $summaryBase): array
    {
        $summaryRow = (clone $summaryBase)
            ->reorder()
            ->select([])
            ->selectRaw('COUNT(DISTINCT deals.id) as total')
            ->selectRaw('SUM(CASE WHEN legals.id IS NULL THEN 1 ELSE 0 END) as new')
            ->selectRaw('SUM(CASE WHEN legals.status = ? THEN 1 ELSE 0 END) as drafting', [LegalStatusEnum::DRAFTING->value])
            ->selectRaw('SUM(CASE WHEN legals.status = ? THEN 1 ELSE 0 END) as pending_bank', [LegalStatusEnum::PENDING_BANK->value])
            ->selectRaw('SUM(CASE WHEN legals.status = ? THEN 1 ELSE 0 END) as pending_customer_signature', [LegalStatusEnum::PENDING_CUSTOMER_SIGNATURE->value])
            ->selectRaw('SUM(CASE WHEN legals.status = ? THEN 1 ELSE 0 END) as completed', [LegalStatusEnum::COMPLETED->value])
            ->first();

        return [
            'total' => (int) ($summaryRow->total ?? 0),
            'new' => (int) ($summaryRow->new ?? 0),
            'drafting' => (int) ($summaryRow->drafting ?? 0),
            'pending_bank' => (int) ($summaryRow->pending_bank ?? 0),
            'pending_customer_signature' => (int) ($summaryRow->pending_customer_signature ?? 0),
            'completed' => (int) ($summaryRow->completed ?? 0),
        ];
    }

    protected static function applyLegalOfficerScope(Builder $query, ?User $user): void
    {
        if (! $user) {
            return;
        }

        if ($user->hasRole(RoleEnum::LEGAL_OFFICER->value) && ! $user->hasRole(RoleEnum::ADMIN->value)) {
            $query->where(function (Builder $legalScopeQuery) use ($user) {
                $legalScopeQuery->whereDoesntHave('legalCase')
                    ->orWhere('legal_officer_id', $user->id);
            });
        }
    }

    protected static function applySelects(Builder $query): void
    {
        $query->leftJoin('leads as clients', 'clients.id', '=', 'deals.lead_id')
            ->leftJoin('users as legal_officers', 'legal_officers.id', '=', 'deals.legal_officer_id')
            ->leftJoin('legals', 'legals.deal_id', '=', 'deals.id')
            ->leftJoin('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->select('deals.*')
            ->addSelect('clients.name as client_name')
            ->addSelect('legal_officers.name as legal_officer_name')
            ->addSelect('legals.lawyer_firm as legal_lawyer_firm')
            ->addSelect('legals.spa_date as legal_spa_date')
            ->addSelect('legals.loan_agreement_date as legal_loan_agreement_date')
            ->addSelect('legals.completion_date as legal_completion_date')
            ->addSelect('legals.stamp_duty as legal_stamp_duty')
            ->addSelect('legals.status as legal_status')
            ->addSelect('deal_pipelines.loan_approved_date as pipeline_loan_approved_date');
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
                ->orWhere('legals.lawyer_firm', 'like', "%{$search}%")
                ->orWhere('legal_officers.name', 'like', "%{$search}%");
        });
    }

    protected static function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        if ($status === 'New') {
            $query->whereDoesntHave('legalCase');

            return;
        }

        $query->whereHas('legalCase', function (Builder $legalQuery) use ($status) {
            $legalQuery->where('status', $status);
        });
    }

    protected static function applySorting(Builder $query, Request $request, bool $canManageLoanRecords): void
    {
        $sortBy = (string) $request->input('sort_by', '');
        $sortDir = ListQuery::sortDirection($request);
        $sortStart = $canManageLoanRecords ? 1 : 0;
        $sortMap = [
            (string) $sortStart => 'deals.deal_id',
            (string) ($sortStart + 1) => 'client_name',
            (string) ($sortStart + 2) => 'legal_officer_name',
            (string) ($sortStart + 3) => 'legal_lawyer_firm',
            (string) ($sortStart + 4) => 'legal_spa_date',
            (string) ($sortStart + 5) => 'legal_loan_agreement_date',
            (string) ($sortStart + 6) => 'legal_completion_date',
            (string) ($sortStart + 7) => 'legal_stamp_duty',
            (string) ($sortStart + 8) => 'legal_status',
            (string) ($sortStart + 9) => 'pipeline_loan_approved_date',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);

            return;
        }

        $query->orderByRaw('CASE WHEN legal_status IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('pipeline_loan_approved_date')
            ->latest('updated_at');
    }
}
