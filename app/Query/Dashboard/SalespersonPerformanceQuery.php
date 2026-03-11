<?php

namespace App\Query\Dashboard;

use App\Enums\LeadStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Support\MonthFilter;
use App\Support\Query\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalespersonPerformanceQuery
{
    public static function build(Request $request, User $user, Carbon $selectedMonth): array
    {
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();
        $perPage = 10;

        $convertedLeadsSub = Lead::query()
            ->where('status', LeadStatusEnum::DEAL->value)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('salesperson_id, COUNT(*) as converted_leads')
            ->groupBy('salesperson_id');

        $completedDealsSub = Deal::query()
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.completed_date')
            ->whereBetween('deal_pipelines.completed_date', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
            ->whereIn('deals.pipeline', [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->selectRaw('deals.salesperson_id as salesperson_id')
            ->selectRaw('COUNT(*) as completed_deals')
            ->selectRaw('AVG(DATEDIFF(deal_pipelines.completed_date, deals.created_at)) as avg_complete_days')
            ->selectRaw('COALESCE(SUM(deals.commission_amount), 0) as total_commission')
            ->groupBy('deals.salesperson_id');

        $salespeopleQuery = User::role(RoleEnum::SALESPERSON->value)
            ->leftJoin('users as leaders', 'leaders.id', '=', 'users.leader_id')
            ->leftJoinSub($convertedLeadsSub, 'converted_leads', 'converted_leads.salesperson_id', '=', 'users.id')
            ->leftJoinSub($completedDealsSub, 'completed_deals', 'completed_deals.salesperson_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->addSelect(DB::raw("COALESCE(leaders.name, '-') as leader"))
            ->addSelect(DB::raw('COALESCE(converted_leads.converted_leads, 0) as converted_leads'))
            ->addSelect(DB::raw('COALESCE(completed_deals.completed_deals, 0) as completed_deals'))
            ->addSelect(DB::raw('CASE WHEN COALESCE(converted_leads.converted_leads, 0) > 0 THEN ROUND((COALESCE(completed_deals.completed_deals, 0) / COALESCE(converted_leads.converted_leads, 0)) * 100, 2) ELSE 0 END as close_rate'))
            ->addSelect(DB::raw('CASE WHEN completed_deals.avg_complete_days IS NULL THEN NULL ELSE ROUND(completed_deals.avg_complete_days, 1) END as avg_complete_days'))
            ->addSelect(DB::raw('COALESCE(completed_deals.total_commission, 0) as total_commission'));

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            $salespeopleQuery->where('users.leader_id', $user->id);
        }

        if ($search = ListQuery::searchTerm($request)) {
            $salespeopleQuery->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('leaders.name', 'like', "%{$search}%");
            });
        }

        $sortIndex = (int) $request->query('sort_by', 0);
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';
        $sortableMap = [
            1 => 'users.name',
            2 => 'leader',
            3 => 'converted_leads',
            4 => 'completed_deals',
            5 => 'close_rate',
            6 => 'avg_complete_days',
            7 => 'total_commission',
        ];

        if (isset($sortableMap[$sortIndex])) {
            $sortColumn = $sortableMap[$sortIndex];
            if ($sortColumn === 'avg_complete_days') {
                $salespeopleQuery->orderByRaw('avg_complete_days IS NULL DESC');
            }
            $salespeopleQuery->orderBy($sortColumn, $sortDir);
        } else {
            $salespeopleQuery->orderBy('users.name');
        }

        $rows = $salespeopleQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($row) {
                return [
                    'name' => $row->name,
                    'leader' => $row->leader,
                    'converted_leads' => (int) $row->converted_leads,
                    'completed_deals' => (int) $row->completed_deals,
                    'close_rate' => (float) $row->close_rate,
                    'avg_complete_days' => $row->avg_complete_days !== null ? (float) $row->avg_complete_days : null,
                    'total_commission' => (float) $row->total_commission,
                ];
            });

        return [
            'pageTitle' => 'Salesperson Performance',
            'pageSubtitle' => sprintf('Monthly conversion and completion summary (%s)', $monthStart->format('F Y')),
            'activeTab' => 'salesperson',
            'rows' => $rows,
            'selectedMonth' => $monthStart->format('Y-m'),
            'monthOptions' => MonthFilter::options($monthStart),
        ];
    }
}
