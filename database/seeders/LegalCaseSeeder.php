<?php

namespace Database\Seeders;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use App\Models\LegalCase;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LegalCaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();
        $now = Carbon::now();

        $legalDeals = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->get();

        foreach ($legalDeals as $deal) {
            $pipeline = $deal->pipeline?->value ?? (string) $deal->pipeline;
            $isClosed = in_array($pipeline, [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ], true);

            $status = $isClosed
                ? 'Completed'
                : $faker->randomElement(['Drafting', 'Pending Bank', 'Pending Customer Signature']);

            $spaDate = $deal->spa_date ?? ($deal->created_at?->copy()->addDays(random_int(14, 45)));
            $loanAgreementDate = $spaDate?->copy()->addDays(random_int(3, 20));
            $completionDate = $status === 'Completed'
                ? $loanAgreementDate?->copy()->addDays(random_int(7, 30))
                : null;

            $createdAt = ($deal->created_at ?? $now)->copy()->addDays(random_int(20, 70));
            $updatedAt = $status === 'Completed'
                ? ($completionDate ?? $createdAt)->copy()
                : $createdAt->copy()->subDays(random_int(10, 25));

            $legalCase = LegalCase::query()->updateOrCreate(
                ['deal_id' => $deal->id],
                [
                    'status' => $status,
                    'lawyer_firm' => $faker->randomElement([
                        'Azman & Partners',
                        'Lee, Tan & Co.',
                        'Khor Legal',
                        'Ibrahim Chambers',
                    ]),
                    'spa_date' => $spaDate?->toDateString(),
                    'loan_agreement_date' => $loanAgreementDate?->toDateString(),
                    'completion_date' => $completionDate?->toDateString(),
                    'stamp_duty' => random_int(1, 100) <= 75,
                ]
            );

            $legalCase->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ])->saveQuietly();
        }
    }
}
