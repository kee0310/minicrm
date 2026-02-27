<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['lead', 'salesperson', 'leader']);
        $user = auth()->user();

        if ($user && !$user->hasRole(RoleEnum::ADMIN->value)) {
            if ($user->hasRole(RoleEnum::LEADER->value)) {
                $query->where(function ($q) use ($user) {
                    $q->where('salesperson_id', $user->id)
                        ->orWhere('leader_id', $user->id);
                });
            } else {
                $query->where('salesperson_id', $user->id);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('deal_id', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($leadQuery) use ($search) {
                        $leadQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('salesperson', function ($salespersonQuery) use ($search) {
                        $salespersonQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leader', function ($leaderQuery) use ($search) {
                        $leaderQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($stage = $request->input('stage')) {
            $query->where('pipeline', $stage);
        }

        $deals = $query->latest()->paginate(20)->withQueryString();
        $stages = PipelineEnum::values();

        return view('deals.index', compact('deals', 'stages'));
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Show the form for creating a new deal.
     *
     * @return \Illuminate\Contracts\View\View
     */
    /*******  78d48508-1fc4-45f3-8f0b-6e90c580d1fa  *******/
    public function create()
    {
        $leads = Lead::where('status', LeadStatusEnum::DEAL->value)->orderBy('name')->get();
        $pipelines = PipelineEnum::creatableCases();
        return view('deals.create', compact('leads', 'pipelines'));
    }

    public function store(StoreDealRequest $request)
    {
        $data = $request->validated();
        $lead = Lead::findOrFail($data['lead_id']);
        $data['salesperson_id'] = $lead->salesperson_id;
        $data['leader_id'] = $lead->leader_id;

        Deal::create($data);
        return redirect()->route('deals.index')->with('success', 'Deal created successfully.');
    }

    public function edit(Deal $deal)
    {
        $leads = Lead::orderBy('name')->get();
        $pipelines = PipelineEnum::creatableCases();
        $isPipelineLocked = $deal->pipeline?->isLockedForManualEdit() ?? false;
        return view('deals.edit', compact('deal', 'leads', 'pipelines', 'isPipelineLocked'));
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $data = $request->validated();
        $lead = Lead::findOrFail($data['lead_id']);
        $data['salesperson_id'] = $lead->salesperson_id;
        $data['leader_id'] = $lead->leader_id;

        $deal->update($data);
        return redirect()->route('deals.index')->with('success', 'Deal updated successfully.');
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return redirect()->route('deals.index')->with('success', 'Deal deleted successfully.');
    }
}
