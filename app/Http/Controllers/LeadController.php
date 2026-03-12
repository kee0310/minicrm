<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Query\Lead\LeadIndexQuery;
use App\Services\DealService;
use App\Services\LeadService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        private LeadService $leadService,
        private DealService $dealService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = auth()->user();
        $query = Lead::query()
            ->visibleTo($user);
        $summaryBase = Lead::query()->visibleTo($user);

        $summary = LeadIndexQuery::summary($summaryBase);

        $statuses = LeadStatusEnum::values();
        $salespersons = $this->dealService->assignableSalespersons($user);

        LeadIndexQuery::build($query, $request);

        $leads = $query->paginate(10)->withQueryString();

        return view('leads.index', compact('leads', 'statuses', 'salespersons', 'summary'));
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeadRequest $request)
    {
        $validated = $request->validated();
        $payload = $this->leadService->buildLeadPayload($validated);
        $lead = Lead::create($payload);

        return redirect()->route('leads.index')->with('success', "Lead {$lead->name} created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $validated = $request->validated();
        $payload = $this->leadService->buildLeadPayload($validated);
        $lead->update($payload);

        return redirect()->back()->with('success', "Lead {$lead->name} updated successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        if ($this->leadService->isLockedDealLead($lead)) {
            return redirect()->back()->with('warning', "Lead {$lead->name} cannot be deleted because it is already in Deal status.");
        }

        $lead->delete();

        return redirect()->back()->with('success', "Lead {$lead->name} deleted successfully.");
    }

    // Lead profile fields are stored directly on leads now.
}
