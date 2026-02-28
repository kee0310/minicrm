<?php

namespace App\Http\Controllers;

use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Client;
use App\Models\Deal;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['client.financialCondition', 'salesperson', 'leader']);
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
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
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
        $clients = Client::orderBy('name')->get();
        $pipelines = PipelineEnum::creatableCases();
        return view('deals.create', compact('clients', 'pipelines'));
    }

    public function store(StoreDealRequest $request)
    {
        $data = $request->validated();
        $client = Client::findOrFail($data['client_id']);

        if (!$client->salesperson_id || !$client->leader_id) {
            return back()
                ->withInput()
                ->withErrors(['client_id' => 'Selected client must have salesperson and leader assigned.']);
        }

        $data['salesperson_id'] = $client->salesperson_id;
        $data['leader_id'] = $client->leader_id;

        Deal::create($data);
        return redirect()->route('deals.index')->with('success', 'Deal created successfully.');
    }

    public function edit(Deal $deal)
    {
        $clients = Client::orderBy('name')->get();
        $selectedClientId = $deal->client_id;
        $pipelines = PipelineEnum::creatableCases();
        $isPipelineLocked = $deal->pipeline?->isLockedForManualEdit() ?? false;
        return view('deals.edit', compact('deal', 'clients', 'selectedClientId', 'pipelines', 'isPipelineLocked'));
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $data = $request->validated();
        $client = Client::findOrFail($data['client_id']);

        if (!$client->salesperson_id || !$client->leader_id) {
            return back()
                ->withInput()
                ->withErrors(['client_id' => 'Selected client must have salesperson and leader assigned.']);
        }

        $data['salesperson_id'] = $client->salesperson_id;
        $data['leader_id'] = $client->leader_id;

        $deal->update($data);
        return redirect()->route('deals.index')->with('success', 'Deal updated successfully.');
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return redirect()->route('deals.index')->with('success', 'Deal deleted successfully.');
    }
}
