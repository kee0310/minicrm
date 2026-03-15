<?php

namespace App\Http\Controllers;

use App\Enums\PipelineEnum;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Deal;
use App\Query\Deal\DealIndexQuery;
use App\Services\DealService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DealController extends Controller
{
    public function __construct(private DealService $dealService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Deal::visibleTo($user);
        $summaryBase = Deal::visibleTo($user);

        $summary = DealIndexQuery::summary($summaryBase);
        DealIndexQuery::build($query, $request);

        $deals = $query->paginate(10)->withQueryString();
        $stages = PipelineEnum::values();
        $leads = $this->dealService->assignableLeads($user);
        $salespersons = $this->dealService->assignableSalespersons($user);
        $pipelines = PipelineEnum::creatableCases();

        $deals = $deals->through(function ($deal) {
            return [
                'id' => $deal->id,
                'deal_code' => $deal->deal_id,
                'lead_id' => $deal->lead_id,
                'lead_name' => $deal->lead_name ?? null,
                'salesperson_id' => $deal->salesperson_id,
                'project_name' => $deal->project_name,
                'developer' => $deal->developer,
                'unit_number' => $deal->unit_number,
                'selling_price' => $deal->selling_price,
                'commission_percentage' => $deal->commission_percentage,
                'commission_amount' => $deal->commission_amount,
                'booking_fee' => $deal->booking_fee,
                'spa_date' => optional($deal->spa_date)->format('Y-m-d'),
                'pipeline' => [
                    'value' => $deal->pipeline?->value,
                    'badge' => $deal->pipeline?->badge(),
                    'locked' => $deal->pipeline?->isLockedForManualEdit() ?? false,
                ],
                'salesperson_name' => $deal->salesperson_name ?? null,
                'leader_name' => $deal->leader_name ?? null,
                'created_at' => optional($deal->created_at)->format('Y-m-d'),
            ];
        });

        $canEditSalesperson = $user?->hasAnyRole([
            \App\Enums\RoleEnum::ADMIN->value,
            \App\Enums\RoleEnum::LEADER->value,
        ]) ?? false;

        return Inertia::render('deals/index', [
            'deals' => $deals,
            'stages' => $stages,
            'pipelines' => collect($pipelines)->map(fn ($pipeline) => [
                'value' => $pipeline->value,
            ])->values(),
            'summary' => $summary,
            'leads' => $leads->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
            ])->values(),
            'salespersons' => $salespersons->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values(),
            'canEditSalesperson' => $canEditSalesperson,
            'currentUserId' => (int) ($user?->id ?? 0),
        ]);
    }

    public function store(StoreDealRequest $request)
    {
        $data = $request->validated();
        $deal = $this->dealService->createDeal($data);

        return redirect()->route('deals.index')->with('success', "Deal {$deal->deal_id} created successfully.");
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $data = $request->validated();
        $this->dealService->updateDeal($deal, $data);

        return redirect()->back()->with('success', "Deal {$deal->deal_id} updated successfully.");
    }

    public function destroy(Request $request, Deal $deal)
    {
        $this->dealService->deleteDeal($deal);

        return $this->redirectWithFlashToPrevious(
            $request,
            route('deals.index'),
            'success',
            "Deal {$deal->deal_id} deleted successfully."
        );
    }
}
