<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Commission;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = $this->scopeCommissionsForCommissionAccess(
            Commission::with(['deal.salesperson'])
        )
            ->latest()
            ->get();

        $statusOptions = ['Unpaid', 'Paid'];

        return view('commissions.index', compact('commissions', 'statusOptions'));
    }

    public function update(Request $request, Commission $commission)
    {
        $this->ensureCanViewCommission($commission);
        $deal = $commission->deal;
        abort_if(!$deal, 422, 'Commission has no linked deal.');

        $data = $request->validate([
            'paid' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:Unpaid,Paid'],
        ]);

        $total = (float) ($deal->commission_amount ?? 0);
        $paid = (float) ($data['paid'] ?? 0);
        $status = $data['payment_status'];

        if ($status === 'Paid') {
            // Business rule: mark fully paid when status is Paid.
            $paid = $total;
        } else {
            abort_if($paid > $total, 422, 'Paid cannot be greater than total commission.');
        }

        $commission->update([
            'paid' => $paid,
            'payment_status' => $status,
        ]);

        if ($status === 'Paid') {
            $deal->update(['pipeline' => \App\Enums\PipelineEnum::COMMISSION_PAID->value]);
        }

        return redirect()->route('commissions.index')->with('success', 'Commission updated.');
    }

    protected function scopeCommissionsForCommissionAccess(Builder $query): Builder
    {
        $user = auth()->user();
        abort_if(!$user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->whereHas('deal', function (Builder $q) use ($user) {
                $q->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    protected function ensureCanViewCommission(Commission $commission): void
    {
        $isAllowed = $this->scopeCommissionsForCommissionAccess(Commission::query())
            ->whereKey($commission->id)
            ->exists();

        abort_unless($isAllowed, 403);
    }
}
