<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Commission;
use App\Models\Deal;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->whereNotNull('commission_amount')
            ->each(function (Deal $deal): void {
                $status = 'Unpaid';
                $paid = 0;
                $createdAt = $deal->deal_closing_date
                    ? $deal->deal_closing_date->copy()
                    : ($deal->updated_at ?? now())->copy();

                if ($deal->pipeline?->value === PipelineEnum::COMMISSION_PAID->value) {
                    $status = 'Paid';
                    $paid = (float) ($deal->commission_amount ?? 0);
                    $createdAt = $createdAt->copy()->addDays(random_int(3, 30));
                }

                $commission = Commission::query()->updateOrCreate(
                    ['deal_id' => $deal->id],
                    [
                        'paid' => $paid,
                        'payment_status' => $status,
                    ]
                );

                $commission->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addDays(random_int(0, 15)),
                ])->saveQuietly();
            });
    }
}
