<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Models\Lead;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;

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

        $leads = $query->latest()->paginate(20)->withQueryString();

        return view('leads.index', compact('leads', 'statuses', 'sources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = \App\Models\User::orderBy('name')->get();
        $leaders = \App\Models\User::role([\App\Enums\RoleEnum::LEADER->value, \App\Enums\RoleEnum::ADMIN->value])->orderBy('name')->get();
        $statuses = LeadStatusEnum::values();
        return view('leads.create', compact('users', 'leaders', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeadRequest $request)
    {
        Lead::create($request->validated());
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
    public function edit(Lead $lead)
    {
        $users = \App\Models\User::orderBy('name')->get();
        $leaders = \App\Models\User::role([\App\Enums\RoleEnum::LEADER->value, \App\Enums\RoleEnum::ADMIN->value])->orderBy('name')->get();
        $statuses = LeadStatusEnum::values();
        return view('leads.edit', compact('lead', 'users', 'leaders', 'statuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());
        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }
}
