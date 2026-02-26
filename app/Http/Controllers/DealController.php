<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\User;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['lead', 'salesperson', 'leader', 'pipeline']);

        if ($search = $request->input('search')) {
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $deals = $query->latest()->paginate(20)->withQueryString();

        return view('deals.index', compact('deals'));
    }

    public function create()
    {
        $leads = Lead::orderBy('name')->get();
        $users = User::whereNull('deleted_at')->orderBy('name')->get();
        $pipelines = Pipeline::orderBy('name')->get();
        return view('deals.create', compact('leads', 'users', 'pipelines'));
    }

    public function store(StoreDealRequest $request)
    {
        Deal::create($request->validated());
        return redirect()->route('deals.index')->with('success', 'Deal created successfully.');
    }

    public function edit(Deal $deal)
    {
        $leads = Lead::orderBy('name')->get();
        $users = User::whereNull('deleted_at')->orderBy('name')->get();
        $pipelines = Pipeline::orderBy('name')->get();
        return view('deals.edit', compact('deal', 'leads', 'users', 'pipelines'));
    }

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $deal->update($request->validated());
        return redirect()->route('deals.index')->with('success', 'Deal updated successfully.');
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();
        return redirect()->route('deals.index')->with('success', 'Deal deleted successfully.');
    }
}
