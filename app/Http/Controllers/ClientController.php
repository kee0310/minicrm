<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
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
            ->orderBy('completeness_rate', 'asc')
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $salespersons = User::role([
            RoleEnum::USER->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->orderBy('name')->get();
        $leaders = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->orderBy('name')->get();

        return view('clients.index', compact('clients', 'salespersons', 'leaders'));
    }

    public function show(Client $client)
    {
        $this->authorizeClientAccess($client);
        $deals = Deal::with(['client', 'salesperson', 'leader'])
            ->where('client_id', $client->id)
            ->latest()
            ->get();

        return view('clients.show', compact('client', 'deals'));
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = Client::create([
            'salesperson_id' => $data['salesperson_id'],
            'leader_id' => $data['leader_id'],
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

    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->authorizeClientAccess($client);
        $data = $request->validated();

        $clientData = [
            'salesperson_id' => $data['salesperson_id'],
            'leader_id' => $data['leader_id'],
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

        $hasDeals = Deal::where('client_id', $client->id)->exists();

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
