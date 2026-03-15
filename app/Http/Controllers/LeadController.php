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
use Inertia\Inertia;

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

        $leads = $query->paginate(10)->withQueryString()
            ->through(function ($lead) {
                $statusValue = $lead->status?->value ?? null;
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'source' => $lead->source,
                    'salesperson_id' => $lead->salesperson_id,
                    'leader_id' => $lead->leader_id,
                    'status' => $statusValue,
                    'status_badge' => $lead->status?->badge(),
                    'age' => $lead->age,
                    'ic_passport' => $lead->ic_passport,
                    'occupation' => $lead->occupation,
                    'company' => $lead->company,
                    'working_years' => $lead->working_years,
                    'monthly_income' => $lead->monthly_income,
                    'fixed_income' => $lead->fixed_income,
                    'created_at' => optional($lead->created_at)->format('Y-m-d'),
                    'salesperson_name' => $lead->salesperson_name ?? null,
                    'leader_name' => $lead->leader_name ?? null,
                ];
            });

        $canEditSalesperson = $user?->hasAnyRole([
            \App\Enums\RoleEnum::ADMIN->value,
            \App\Enums\RoleEnum::LEADER->value,
        ]) ?? false;

        return Inertia::render('leads/index', [
            'leads' => $leads,
            'statuses' => $statuses,
            'salespersons' => $salespersons->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values(),
            'summary' => $summary,
            'canEditSalesperson' => $canEditSalesperson,
        ]);
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
    public function destroy(Request $request, Lead $lead)
    {
        if ($this->leadService->isLockedDealLead($lead)) {
            return $this->redirectWithFlashToPrevious(
                $request,
                route('leads.index'),
                'warning',
                "Lead {$lead->name} cannot be deleted because it has existing deals."
            );
        }

        $lead->delete();

        return $this->redirectWithFlashToPrevious(
            $request,
            route('leads.index'),
            'success',
            "Lead {$lead->name} deleted successfully."
        );
    }

    // Lead profile fields are stored directly on leads now.
}
