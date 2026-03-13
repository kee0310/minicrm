<?php

namespace App\Query\Lead;

use App\Enums\LeadStatusEnum;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LeadIndexQuery
{
    public static function summary(Builder $summaryBase): array
    {
        $statusCounts = (clone $summaryBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => (int) $statusCounts->sum(),
        ];

        foreach (LeadStatusEnum::values() as $leadStatus) {
            $summary[strtolower(str_replace(' ', '_', $leadStatus))] = (int) ($statusCounts[$leadStatus] ?? 0);
        }

        return $summary;
    }

    public static function build(Builder $query, Request $request): Builder
    {
        $query->leftJoin('users as salespersons', 'salespersons.id', '=', 'leads.salesperson_id')
            ->leftJoin('users as leaders', 'leaders.id', '=', 'leads.leader_id')
            ->leftJoin('deals as latest_deals', 'latest_deals.lead_id', '=', 'leads.id')
            ->select([
                'leads.id',
                'leads.name',
                'leads.email',
                'leads.phone',
                'leads.source',
                'leads.salesperson_id',
                'leads.leader_id',
                'leads.status',
                'leads.age',
                'leads.ic_passport',
                'leads.occupation',
                'leads.company',
                'leads.working_years',
                'leads.monthly_income',
                'leads.fixed_income',
                'leads.created_at',
            ])
            ->addSelect([
                'salespersons.name as salesperson_name',
                'leaders.name as leader_name',
            ])
            ->selectRaw('MAX(latest_deals.created_at) as latest_deal_created_at')
            ->groupBy([
                'leads.id',
                'leads.name',
                'leads.email',
                'leads.phone',
                'leads.source',
                'leads.salesperson_id',
                'leads.leader_id',
                'leads.status',
                'leads.age',
                'leads.ic_passport',
                'leads.occupation',
                'leads.company',
                'leads.working_years',
                'leads.monthly_income',
                'leads.fixed_income',
                'leads.created_at',
                'salespersons.name',
                'leaders.name',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyStatusFilter($query, $request->input('status'));
        self::applySorting($query, $request);

        return $query;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('leads.name', 'like', "%{$search}%")
                ->orWhere('leads.email', 'like', "%{$search}%")
                ->orWhere('leads.phone', 'like', "%{$search}%")
                ->orWhereHas('salesperson', function (Builder $salespersonQuery) use ($search) {
                    $salespersonQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('leader', function (Builder $leaderQuery) use ($search) {
                    $leaderQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    protected static function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status) {
            $query->where('leads.status', $status);
        }
    }

    protected static function applySorting(Builder $query, Request $request): void
    {
        $sortMap = [
            '1' => 'leads.name',
            '2' => 'leads.email',
            '3' => 'leads.phone',
            '4' => 'leads.source',
            '5' => 'salesperson_name',
            '6' => 'leader_name',
            '7' => 'leads.status',
            '8' => 'leads.created_at',
        ];

        ListQuery::applySort($query, $request, $sortMap, function (Builder $query) {
            $query->orderByDesc('leads.created_at');
        });
    }
}
