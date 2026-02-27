<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Deal;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->scopedClientsQuery()->with(['lead.salesperson', 'lead.leader']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('ic_passport', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'New' THEN 0
                    WHEN status = 'Completed' THEN 100
                    WHEN status LIKE '%\\%%' THEN CAST(REPLACE(status, '%', '') AS UNSIGNED)
                    ELSE 999
                END ASC
            ")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        $nextClientId = sprintf('CL-%06d', (Client::max('id') ?? 0) + 1);
        return view('clients.create', compact('nextClientId'));
    }

    public function show(Client $client)
    {
        $this->authorizeClientAccess($client);
        $client->load(['lead.salesperson', 'lead.leader']);

        $deals = collect();
        if (!is_null($client->lead_id)) {
            $deals = Deal::with(['lead', 'salesperson', 'leader'])
                ->where('lead_id', $client->lead_id)
                ->latest()
                ->get();
        }

        return view('clients.show', compact('client', 'deals'));
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = Client::create([
            'lead_id' => $data['lead_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'age' => $data['age'] ?? null,
            'ic_passport' => $data['ic_passport'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'company' => $data['company'] ?? null,
            'monthly_income' => $data['monthly_income'] ?? null,
            'status' => 'New',
            'completeness_rate' => 0,
        ]);

        $client->recalculateCompletenessAndStatus();

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function edit(Client $client)
    {
        $this->authorizeClientAccess($client);
        $client->load(['lead.salesperson', 'lead.leader']);
        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->authorizeClientAccess($client);
        $data = $request->validated();

        $clientData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'age' => $data['age'] ?? null,
            'ic_passport' => $data['ic_passport'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'company' => $data['company'] ?? null,
            'monthly_income' => $data['monthly_income'] ?? null,
        ];
        $client->update($clientData);
        $client->recalculateCompletenessAndStatus();

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $this->authorizeClientAccess($client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    protected function scopedClientsQuery()
    {
        $query = Client::query();
        $user = auth()->user();

        if ($user && !$user->hasRole(RoleEnum::ADMIN->value)) {
            if ($user->hasRole(RoleEnum::LEADER->value)) {
                $query->where(function ($scopedQuery) use ($user) {
                    $scopedQuery->whereHas('lead', function ($q) use ($user) {
                        $q->where('salesperson_id', $user->id)
                            ->orWhere('leader_id', $user->id);
                    })->orWhereNull('lead_id');
                });
            } else {
                $query->where(function ($scopedQuery) use ($user) {
                    $scopedQuery->whereHas('lead', function ($q) use ($user) {
                        $q->where('salesperson_id', $user->id);
                    })->orWhereNull('lead_id');
                });
            }
        }

        return $query;
    }

    protected function authorizeClientAccess(Client $client): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return;
        }

        $lead = $client->lead;
        if (!$lead) {
            return;
        }
        if ($user->hasRole(RoleEnum::LEADER->value)) {
            abort_unless(
                $lead && ($lead->salesperson_id === $user->id || $lead->leader_id === $user->id),
                403
            );
            return;
        }

        abort_unless($lead && $lead->salesperson_id === $user->id, 403);
    }
}
