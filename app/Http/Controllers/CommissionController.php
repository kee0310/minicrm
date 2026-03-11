<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Query\Commission\CommissionIndexQuery;
use App\Services\CommissionService;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(private CommissionService $commissionService) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = CommissionIndexQuery::build(
            Commission::with(['deal.salesperson', 'deal.client']),
            $request,
            $user
        );

        $summaryBase = Commission::query()
            ->join('deals', 'deals.id', '=', 'commissions.deal_id')
            ->select('commissions.id', 'commissions.payment_status', 'commissions.paid');

        $summary = CommissionIndexQuery::summary($summaryBase, $user);
        $commissions = $query->paginate(10)->withQueryString();

        $statusOptions = ['Unpaid', 'Paid'];

        return view('commissions.index', compact('commissions', 'statusOptions', 'summary'));
    }

    public function update(Request $request, Commission $commission)
    {
        CommissionIndexQuery::ensureCanView($commission, $request->user());

        $data = $request->validate([
            'paid' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:Unpaid,Paid'],
        ]);

        $paid = (float) ($data['paid'] ?? 0);
        $status = $data['payment_status'];

        $this->commissionService->updateCommission($commission, $paid, $status);
        $dealCode = $commission->deal?->deal_id ?? ('#' . $commission->deal_id);

        return redirect()->back()->with('success', "Commission for deal {$dealCode} updated successfully.");
    }
}
