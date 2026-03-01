<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Models\Lead;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;
use Illuminate\Support\Arr;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lead::with(['salesperson', 'leader']);

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
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('salesperson', function ($salespersonQuery) use ($search) {
                        $salespersonQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leader', function ($leaderQuery) use ($search) {
                        $leaderQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $statuses = LeadStatusEnum::values();
        $sources = (clone $query)->select('source')
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');
        $users = \App\Models\User::orderBy('name')->get();
        $leaders = \App\Models\User::role([\App\Enums\RoleEnum::LEADER->value, \App\Enums\RoleEnum::ADMIN->value])
            ->orderBy('name')
            ->get();

        $leads = $query->latest()->paginate(20)->withQueryString();

        return view('leads.index', compact('leads', 'statuses', 'sources', 'users', 'leaders'));
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
        $lead = Lead::create($this->extractLeadPayload($validated));
        $this->syncClientProfileWhenDealStatus($lead, $validated);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
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
        if ($this->isLockedDealLead($lead)) {
            return redirect()->route('leads.index')->with('warning', 'Lead with Deal status cannot be edited.');
        }

        $validated = $request->validated();
        $lead->update($this->extractLeadPayload($validated));
        $this->syncClientProfileWhenDealStatus($lead, $validated);

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        if ($this->isLockedDealLead($lead)) {
            return redirect()->route('leads.index')->with('warning', 'Lead with Deal status cannot be deleted.');
        }

        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }

    protected function isLockedDealLead(Lead $lead): bool
    {
        return ($lead->status?->value ?? $lead->status) === LeadStatusEnum::DEAL->value;
    }

    protected function extractLeadPayload(array $validated): array
    {
        return Arr::only($validated, [
            'name',
            'email',
            'phone',
            'source',
            'salesperson_id',
            'leader_id',
            'status',
        ]);
    }

    protected function syncClientProfileWhenDealStatus(Lead $lead, array $validated): void
    {
        $status = $lead->status instanceof LeadStatusEnum
            ? $lead->status
            : LeadStatusEnum::tryFrom((string) $lead->status);

        if ($status !== LeadStatusEnum::DEAL) {
            return;
        }

        $client = $lead->client()->firstOrNew(['email' => $lead->email]);
        $client->fill([
            'name' => $lead->name,
            'phone' => $lead->phone,
            'salesperson_id' => $lead->salesperson_id,
            'leader_id' => $lead->leader_id,
            'age' => $validated['age'] ?? null,
            'ic_passport' => $validated['ic_passport'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'company' => $validated['company'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
        ]);
        $client->save();
        $client->recalculateCompletenessAndStatus();
    }
}
