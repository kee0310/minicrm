<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Commission;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                $isPaid = ($deal->pipeline?->value ?? (string) $deal->pipeline) === PipelineEnum::COMMISSION_PAID->value;
                $pipelineDates = DB::table('deal_pipelines')
                    ->where('deal_id', $deal->id)
                    ->first(['completed_date', 'commission_paid_date']);

                $baseDate = $this->clampToSeedWindow(
                    Carbon::parse($pipelineDates?->completed_date ?? $deal->updated_at ?? $deal->created_at)
                );

                Commission::query()->updateOrCreate(
                    ['deal_id' => $deal->id],
                    [
                        'paid' => $isPaid ? (float) ($deal->commission_amount ?? 0) : 0,
                        'payment_status' => $isPaid ? 'Paid' : 'Unpaid',
                        'created_at' => $baseDate,
                        'updated_at' => $isPaid
                            ? $this->clampToSeedWindow(Carbon::parse($pipelineDates?->commission_paid_date ?? $baseDate->copy()->addDays(random_int(2, 20))))
                            : $this->clampToSeedWindow($baseDate->copy()->addDays(random_int(0, 12))),
                    ]
                );
            });
    }

    private function clampToSeedWindow(\Carbon\CarbonInterface $date): \Carbon\CarbonInterface
    {
        $year = (int) now()->year;
        $start = Carbon::create($year, 1, 1, 0, 0, 0);
        $end = Carbon::create($year, 2, 1, 23, 59, 59)->endOfMonth();

        if ($date->lt($start)) {
            return $start;
        }

        if ($date->gt($end)) {
            return $end;
        }

        return $date;
    }
}
