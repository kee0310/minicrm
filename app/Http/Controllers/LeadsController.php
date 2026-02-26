<?php

namespace App\Http\Controllers;

use App\Models\Leads;
use App\Http\Requests\StoreLeadsRequest;
use App\Http\Requests\UpdateLeadsRequest;
use Illuminate\Http\Request;
use App\RoleEnum;

class LeadsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Leads::query();

        $user = auth()->user();
        // If the current user is not an Admin, only show leads assigned to them
        if ($user && !$user->hasRole(RoleEnum::ADMIN->value)) {
            $query->where('assigned_to', $user->name);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $statuses = Leads::select('status')->distinct()->pluck('status');

        $leads = $query->latest()->paginate(20)->withQueryString();

        return view('leads.index', compact('leads', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = \App\Models\User::whereNull('deleted_at')->orderBy('name')->get();
        $leaders = \App\Models\User::role([\App\RoleEnum::LEADER->value, \App\RoleEnum::ADMIN->value])->whereNull('deleted_at')->orderBy('name')->get();
        return view('leads.create', compact('users', 'leaders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeadsRequest $request)
    {
        Leads::create($request->validated());
        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Leads $lead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Leads $lead)
    {
        $users = \App\Models\User::whereNull('deleted_at')->orderBy('name')->get();
        $leaders = \App\Models\User::role([\App\RoleEnum::LEADER->value, \App\RoleEnum::ADMIN->value])->whereNull('deleted_at')->orderBy('name')->get();
        return view('leads.edit', compact('lead', 'users', 'leaders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeadsRequest $request, Leads $lead)
    {
        $lead->update($request->validated());
        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leads $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }
}
