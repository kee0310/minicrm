<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LegalCase;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegalCaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();

        $legalDeals = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->get();

        foreach ($legalDeals as $deal) {
            $pipeline = $deal->pipeline?->value ?? (string) $deal->pipeline;
            $isCompleted = in_array($pipeline, [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ], true);

            $pipelineDates = DB::table('deal_pipelines')
                ->where('deal_id', $deal->id)
                ->first(['spa_signed_date', 'loan_approved_date', 'completed_date', 'legal_processing_date']);

            $spaDate = $this->clampToSeedWindow(Carbon::parse($pipelineDates?->spa_signed_date ?? $deal->created_at));
            $loanAgreementDate = $this->clampToSeedWindow(
                Carbon::parse($pipelineDates?->loan_approved_date ?? $deal->updated_at ?? $deal->created_at)->addDays(random_int(2, 15))
            );

            LegalCase::query()->updateOrCreate(
                ['deal_id' => $deal->id],
                [
                    'status' => $isCompleted
                        ? 'Completed'
                        : $faker->randomElement(['Drafting', 'Pending Bank', 'Pending Customer Signature']),
                    'lawyer_firm' => $faker->randomElement([
                        'Azman & Partners',
                        'Lee, Tan & Co.',
                        'Khor Legal',
                        'Ibrahim Chambers',
                    ]),
                    'spa_date' => $spaDate,
                    'loan_agreement_date' => $loanAgreementDate,
                    'completion_date' => $isCompleted
                        ? $this->clampToSeedWindow(Carbon::parse($pipelineDates?->completed_date ?? $pipelineDates?->legal_processing_date ?? $deal->updated_at)->copy())
                        : null,
                    'stamp_duty' => $isCompleted ? random_int(1, 100) <= 92 : random_int(1, 100) <= 45,
                    'created_at' => $this->clampToSeedWindow($deal->created_at?->copy() ?? now()),
                    'updated_at' => $this->clampToSeedWindow(($deal->updated_at?->copy() ?? now())->addDays(random_int(0, 5))),
                ]
            );
        }
    }

    private function clampToSeedWindow(CarbonInterface $date): CarbonInterface
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
