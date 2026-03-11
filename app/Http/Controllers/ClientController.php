<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Query\Client\ClientIndexQuery;
use App\Services\LeadService;
use Illuminate\Http\Request;

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

        return view('clients.index', compact('clients'));
    }

    public function show(Lead $lead)
    {
        $this->leadService->ensureLeadAccess(auth()->user(), $lead);
        $deals = Deal::with(['client', 'salesperson', 'leader', 'loanOfficer', 'legalOfficer'])
            ->where('lead_id', $lead->id)
            ->latest('updated_at')
            ->get();

        return view('clients.show', [
            'client' => $lead,
            'deals' => $deals,
        ]);
    }
}
