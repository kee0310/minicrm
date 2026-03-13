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

        return view('deals.index', compact('deals', 'stages', 'leads', 'salespersons', 'pipelines', 'summary'));
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
