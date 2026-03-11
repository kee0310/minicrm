<?php

namespace App\Query\Client;

use App\Models\Lead;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientIndexQuery
{
    public static function build(Builder $baseQuery, Request $request): Builder
    {
        self::applySearch($baseQuery, ListQuery::searchTerm($request));

        $scopedLeads = (clone $baseQuery)
            ->select([
                'leads.id',
                'leads.name',
                'leads.email',
                'leads.phone',
                'leads.age',
                'leads.ic_passport',
                'leads.occupation',
                'leads.company',
                'leads.working_years',
                'leads.monthly_income',
                'leads.fixed_income',
                'leads.created_at',
            ])
            ->orderByDesc('leads.created_at')
            ->orderByDesc('leads.id');

        $latestByEmail = (clone $baseQuery)
            ->selectRaw('leads.email, MAX(leads.created_at) as max_created_at')
            ->groupBy('leads.email');

        $latestIdByEmail = (clone $baseQuery)
            ->joinSub($latestByEmail, 'latest_dates', function ($join) {
                $join->on('latest_dates.email', '=', 'leads.email')
                    ->on('latest_dates.max_created_at', '=', 'leads.created_at');
            })
            ->selectRaw('leads.email, MAX(leads.id) as max_id')
            ->groupBy('leads.email');

        $query = Lead::query()
            ->fromSub($scopedLeads, 'latest')
            ->joinSub($latestIdByEmail, 'latest_ids', function ($join) {
                $join->on('latest_ids.max_id', '=', 'latest.id');
            });

        $sortMap = [
            '1' => 'latest.name',
            '2' => 'latest.name',
            '3' => 'latest.email',
            '4' => 'latest.phone',
            '5' => 'latest.created_at',
        ];

        ListQuery::applySort($query, $request, $sortMap, function (Builder $query) {
            $query->orderByDesc('latest.created_at');
        });

        return $query;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($search) {
            $searchQuery->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('ic_passport', 'like', "%{$search}%");
        });
    }
}
