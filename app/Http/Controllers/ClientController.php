<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Query\Client\ClientIndexQuery;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function __construct(private LeadService $leadService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $base = Lead::query()
            ->visibleTo($user)
            ->where('status', LeadStatusEnum::DEAL->value);
        $query = ClientIndexQuery::build($base, $request);
        $clients = $query->paginate(10)->withQueryString();

        $clients = $clients->through(fn ($client) => [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'age' => $client->age,
            'occupation' => $client->occupation,
            'company' => $client->company,
        ]);

        return Inertia::render('clients/index', [
            'clients' => $clients,
        ]);
    }

    public function show(Lead $lead)
    {
        $this->leadService->ensureLeadAccess(auth()->user(), $lead);
        $leadIds = Lead::query()
            ->visibleTo(auth()->user())
            ->where('email', $lead->email)
            ->pluck('id');
        $deals = Deal::with(['client', 'salesperson', 'leader', 'loanOfficer', 'legalOfficer'])
            ->whereIn('lead_id', $leadIds)
            ->latest('updated_at')
            ->get();

        return Inertia::render('clients/show', [
            'client' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'age' => $lead->age,
                'ic_passport' => $lead->ic_passport,
                'occupation' => $lead->occupation,
                'company' => $lead->company,
                'working_years' => $lead->working_years,
                'monthly_income' => $lead->monthly_income,
                'fixed_income' => $lead->fixed_income,
            ],
            'deals' => $deals->map(fn ($deal) => [
                'id' => $deal->id,
                'deal_code' => $deal->deal_id,
                'project_name' => $deal->project_name,
                'developer' => $deal->developer,
                'unit_number' => $deal->unit_number,
                'selling_price' => $deal->selling_price,
                'created_at' => optional($deal->created_at)->format('Y-m-d'),
                'salesperson_name' => $deal->salesperson?->name,
                'leader_name' => $deal->leader?->name,
                'loan_officer_name' => $deal->loanOfficer?->name,
                'legal_officer_name' => $deal->legalOfficer?->name,
                'pipeline' => [
                    'value' => $deal->pipeline?->value,
                    'badge' => $deal->pipeline?->badge(),
                ],
            ])->values(),
        ]);
    }
}
