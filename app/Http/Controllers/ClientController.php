<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

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
        $deals = Deal::with(['lead', 'salesperson', 'leader'])
            ->whereHas('lead', function ($query) use ($client) {
                $query->where('email', $client->email);
            })
            ->latest()
            ->get();

        return view('clients.show', compact('client', 'deals'));
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = Client::create([
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

        $hasDeals = Deal::whereHas('lead', function ($query) use ($client) {
            $query->where('email', $client->email);
        })->exists();

        if ($hasDeals) {
            return redirect()->route('clients.index')->with('warning', 'Client cannot be deleted because deals already exist.');
        }

        DB::transaction(function () use ($client) {
            Lead::where('email', $client->email)->delete();
            $client->delete();
        });

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    protected function authorizeClientAccess(Client $client): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }
    }
}
