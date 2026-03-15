<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Query\Commission\CommissionIndexQuery;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        $commissions = $commissions->through(function ($commission) {
            $deal = $commission->deal;
            $total = (float) ($deal?->commission_amount ?? 0);
            $paid = (float) ($commission?->paid ?? 0);
            $remaining = max($total - $paid, 0);
            $paymentStatus = $commission?->payment_status ?? 'Unpaid';

            return [
                'id' => $commission->id,
                'deal_id' => $deal?->id,
                'deal_code' => $deal?->deal_id,
                'project_name' => $deal?->project_name,
                'salesperson_name' => $deal?->salesperson?->name,
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remaining,
                'payment_status' => $paymentStatus,
                'deal_completed_date' => $commission?->deal_completed_date
                    ? \Illuminate\Support\Carbon::parse($commission->deal_completed_date)->format('Y-m-d')
                    : null,
                'deal_commission_paid_date' => $commission?->deal_commission_paid_date
                    ? \Illuminate\Support\Carbon::parse($commission->deal_commission_paid_date)->format('Y-m-d')
                    : null,
            ];
        });

        return Inertia::render('commissions/index', [
            'commissions' => $commissions,
            'statusOptions' => $statusOptions,
            'summary' => $summary,
        ]);
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
        $dealCode = $commission->deal?->deal_id ?? ('#'.$commission->deal_id);

        return redirect()->back()->with('success', "Commission for deal {$dealCode} updated successfully.");
    }
}
