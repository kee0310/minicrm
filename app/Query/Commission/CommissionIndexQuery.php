<?php

namespace App\Query\Commission;

use App\Enums\RoleEnum;
use App\Models\Commission;
use App\Models\User;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CommissionIndexQuery
{
    public static function ensureCanView(Commission $commission, ?User $user): void
    {
        $isAllowed = self::scopeCommissionsForCommissionAccess(Commission::query(), $user)
            ->whereKey($commission->id)
            ->exists();

        abort_unless($isAllowed, 403);
    }

    public static function build(Builder $query, Request $request, ?User $user): Builder
    {
        $query = self::scopeCommissionsForCommissionAccess($query, $user);

        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyStatusFilter($query, $request->input('status'));
        self::applySelects($query);
        self::applySorting($query, $request);

        return $query;
    }

    public static function summary(Builder $summaryBase, ?User $user): array
    {
        $summaryBase = self::scopeSummaryByRole($summaryBase, $user);

        return [
            'eligible' => (clone $summaryBase)->count('commissions.id'),
            'pending_approval' => (clone $summaryBase)
                ->where(function (Builder $q) {
                    $q->whereNull('commissions.payment_status')
                        ->orWhere('commissions.payment_status', 'Unpaid');
                })
                ->where(function (Builder $q) {
                    $q->whereNull('commissions.paid')
                        ->orWhere('commissions.paid', 0);
                })
                ->count('commissions.id'),
            'paid' => (clone $summaryBase)
                ->where('commissions.payment_status', 'Paid')
                ->count('commissions.id'),
        ];
    }

    protected static function scopeCommissionsForCommissionAccess(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->whereHas('deal', function (Builder $dealQuery) use ($user) {
                $dealQuery->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    protected static function scopeSummaryByRole(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return $query->where('deals.leader_id', $user->id);
        }

        if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
            return $query->where('deals.salesperson_id', $user->id);
        }

        return self::scopeCommissionsForCommissionAccess($query, $user);
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->whereHas('deal', function (Builder $dealQuery) use ($search) {
            $dealQuery->where('deals.deal_id', 'like', "%{$search}%")
                ->orWhere('deals.project_name', 'like', "%{$search}%")
                ->orWhereHas('salesperson', function (Builder $salespersonQuery) use ($search) {
                    $salespersonQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    protected static function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        if ($status === 'Paid') {
            $query->where('payment_status', 'Paid');

            return;
        }

        if ($status === 'Unpaid') {
            $query->where(function (Builder $statusQuery) {
                $statusQuery->whereNull('payment_status')
                    ->orWhere('payment_status', 'Unpaid');
            });
        }
    }

    protected static function applySelects(Builder $query): void
    {
        $query->join('deals', 'deals.id', '=', 'commissions.deal_id')
            ->leftJoin('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->leftJoin('users as salespersons', 'salespersons.id', '=', 'deals.salesperson_id')
            ->select('commissions.*')
            ->addSelect('deals.commission_amount as total')
            ->addSelect('deal_pipelines.completed_date as deal_completed_date')
            ->addSelect('deal_pipelines.commission_paid_date as deal_commission_paid_date')
            ->selectRaw('GREATEST(deals.commission_amount - COALESCE(commissions.paid, 0), 0) as remaining');
    }

    protected static function applySorting(Builder $query, Request $request): void
    {
        $sortMap = [
            '1' => 'deals.deal_id',
            '2' => 'salespersons.name',
            '3' => 'total',
            '4' => 'commissions.paid',
            '5' => 'remaining',
            '6' => 'commissions.payment_status',
            '7' => 'deal_pipelines.completed_date',
            '8' => 'deal_pipelines.commission_paid_date',
        ];

        ListQuery::applySort($query, $request, $sortMap, function (Builder $query) {
            $query->orderByDesc('deal_pipelines.completed_date')
                ->orderByDesc('commissions.updated_at');
        });
    }
}
