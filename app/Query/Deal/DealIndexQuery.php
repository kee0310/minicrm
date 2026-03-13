<?php

namespace App\Query\Deal;

use App\Enums\PipelineEnum;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DealIndexQuery
{
    public static function summary(Builder $summaryBase): array
    {
        $stageCounts = (clone $summaryBase)
            ->selectRaw('pipeline, COUNT(*) as total')
            ->groupBy('pipeline')
            ->pluck('total', 'pipeline');

        $summary = [
            'total' => (int) $stageCounts->sum(),
        ];

        foreach (PipelineEnum::values() as $stage) {
            $summary[strtolower(str_replace(' ', '_', $stage))] = (int) ($stageCounts[$stage] ?? 0);
        }

        return $summary;
    }

    public static function build(Builder $query, Request $request): Builder
    {
        $query->leftJoin('leads', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('users as salespersons', 'salespersons.id', '=', 'deals.salesperson_id')
            ->leftJoin('users as leaders', 'leaders.id', '=', 'deals.leader_id')
            ->select([
                'deals.id',
                'deals.deal_id',
                'deals.lead_id',
                'deals.project_name',
                'deals.developer',
                'deals.unit_number',
                'deals.selling_price',
                'deals.commission_percentage',
                'deals.commission_amount',
                'deals.salesperson_id',
                'deals.leader_id',
                'deals.booking_fee',
                'deals.pipeline',
                'deals.created_at',
            ])
            ->addSelect([
                'leads.name as lead_name',
                'salespersons.name as salesperson_name',
                'leaders.name as leader_name',
            ]);

        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyStageFilter($query, $request->input('stage'));
        self::applySorting($query, $request);

        return $query;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('deals.deal_id', 'like', "%{$search}%")
                ->orWhere('deals.project_name', 'like', "%{$search}%")
                ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('salesperson', function (Builder $salespersonQuery) use ($search) {
                    $salespersonQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('leader', function (Builder $leaderQuery) use ($search) {
                    $leaderQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    protected static function applyStageFilter(Builder $query, ?string $stage): void
    {
        if ($stage) {
            $query->where('pipeline', $stage);
        }
    }

    protected static function applySorting(Builder $query, Request $request): void
    {
        $sortMap = [
            '1' => 'lead_name',
            '2' => 'deals.deal_id',
            '3' => 'selling_price',
            '4' => 'commission_amount',
            '5' => 'pipeline',
            '6' => 'salesperson_name',
            '7' => 'leader_name',
            '8' => 'created_at',
        ];

        ListQuery::applySort($query, $request, $sortMap, function (Builder $query) {
            $query->latest('created_at');
        });
    }
}
