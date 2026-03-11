<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DealPipelineSeeder extends Seeder
{
    private const STAGE_COLUMNS = [
        'lead_date',
        'viewing_date',
        'booking_date',
        'spa_signed_date',
        'loan_submitted_date',
        'loan_approved_date',
        'legal_processing_date',
        'completed_date',
        'commission_paid_date',
    ];

    private const STAGE_ORDER = [
        PipelineEnum::LEAD->value => 0,
        PipelineEnum::VIEWING->value => 1,
        PipelineEnum::BOOKING->value => 2,
        PipelineEnum::SPA_SIGNED->value => 3,
        PipelineEnum::LOAN_SUBMITTED->value => 4,
        PipelineEnum::LOAN_APPROVED->value => 5,
        PipelineEnum::LEGAL_PROCESSING->value => 6,
        PipelineEnum::COMPLETED->value => 7,
        PipelineEnum::COMMISSION_PAID->value => 8,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('deal_pipelines') || ! Schema::hasTable('deals')) {
            return;
        }

        $hasLegacyDateColumns = collect(self::STAGE_COLUMNS)
            ->contains(fn (string $column) => Schema::hasColumn('deals', $column));

        Deal::query()
            ->select(['id', 'pipeline', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($deals) use ($hasLegacyDateColumns) {
                $payloadRows = [];

                foreach ($deals as $deal) {
                    $pipelineValue = $deal->pipeline instanceof PipelineEnum
                        ? $deal->pipeline->value
                        : (string) $deal->pipeline;

                    $payload = [
                        'deal_id' => $deal->id,
                        'lead_date' => $this->clampToSeedWindow($deal->created_at?->copy() ?? now()),
                        'viewing_date' => null,
                        'booking_date' => null,
                        'spa_signed_date' => null,
                        'loan_submitted_date' => null,
                        'loan_approved_date' => null,
                        'legal_processing_date' => null,
                        'completed_date' => null,
                        'commission_paid_date' => null,
                        'created_at' => $this->clampToSeedWindow($deal->created_at?->copy() ?? now()),
                        'updated_at' => $this->clampToSeedWindow($deal->updated_at?->copy() ?? now()),
                    ];

                    if ($hasLegacyDateColumns) {
                        $legacy = DB::table('deals')
                            ->where('id', $deal->id)
                            ->first(self::STAGE_COLUMNS);

                        foreach (self::STAGE_COLUMNS as $column) {
                            if (! is_null($legacy?->{$column})) {
                                $payload[$column] = $legacy->{$column};
                            }
                        }
                    }

                    $pipelineOrder = self::STAGE_ORDER[$pipelineValue] ?? 0;
                    $cursor = $deal->created_at?->copy() ?? now()->subDays(7);

                    foreach (self::STAGE_COLUMNS as $index => $column) {
                        if (! is_null($payload[$column])) {
                            $cursor = $payload[$column];
                            continue;
                        }

                        if ($index <= $pipelineOrder) {
                            $cursor = $cursor->copy()->addDays(2);
                            $payload[$column] = $this->clampToSeedWindow($cursor);
                        }
                    }

                    $payloadRows[] = $payload;
                }

                if (! empty($payloadRows)) {
                    DB::table('deal_pipelines')->upsert(
                        $payloadRows,
                        ['deal_id'],
                        array_merge(self::STAGE_COLUMNS, ['created_at', 'updated_at'])
                    );
                }
            }, 'id');
    }

    private function clampToSeedWindow(\Carbon\CarbonInterface $date): \Carbon\CarbonInterface
    {
        $year = (int) now()->year;
        $start = \Carbon\Carbon::create($year, 1, 1, 0, 0, 0);
        $end = \Carbon\Carbon::create($year, 2, 1, 23, 59, 59)->endOfMonth();

        if ($date->lt($start)) {
            return $start;
        }

        if ($date->gt($end)) {
            return $end;
        }

        return $date;
    }
}
