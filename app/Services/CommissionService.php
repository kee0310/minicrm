<?php

namespace App\Services;

use App\Enums\PipelineEnum;
use App\Models\Commission;

class CommissionService
{
    public function updateCommission(Commission $commission, float $paid, string $status): void
    {
        $deal = $commission->deal;
        abort_if(! $deal, 422, 'Commission has no linked deal.');

        $total = (float) ($deal->commission_amount ?? 0);

        if ($status === 'Paid') {
            $paid = $total;
        } else {
            abort_if($paid > $total, 422, 'Paid cannot be greater than total commission.');
        }

        $commission->update([
            'paid' => $paid,
            'payment_status' => $status,
        ]);

        if ($status === 'Paid') {
            $deal->syncPipelineStage(PipelineEnum::COMMISSION_PAID);
        }
    }
}
