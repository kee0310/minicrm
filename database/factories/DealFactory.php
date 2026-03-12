<?php

namespace Database\Factories;

use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sellingPrice = $this->faker->randomFloat(2, 100000, 3000000);
        $commissionPercentage = $this->faker->randomFloat(2, 0.5, 5);
        $stage = $this->faker->randomElement(PipelineEnum::values());
        $stageOrder = [
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

        $createdAt = CarbonImmutable::instance($this->faker->dateTimeBetween('-12 months', 'now'));
        $leadDate = $createdAt;
        $viewingDate = ($stageOrder[$stage] ?? 0) >= 1 ? $leadDate->addDays(random_int(2, 15)) : null;
        $bookingDate = ($stageOrder[$stage] ?? 0) >= 2 ? ($viewingDate ?? $leadDate)->addDays(random_int(2, 12)) : null;
        $spaSignedDate = ($stageOrder[$stage] ?? 0) >= 3 ? ($bookingDate ?? $leadDate)->addDays(random_int(4, 20)) : null;
        $loanSubmittedDate = ($stageOrder[$stage] ?? 0) >= 4 ? ($spaSignedDate ?? $leadDate)->addDays(random_int(2, 15)) : null;
        $loanApprovedDate = ($stageOrder[$stage] ?? 0) >= 5 ? ($loanSubmittedDate ?? $leadDate)->addDays(random_int(4, 25)) : null;
        $legalProcessingDate = ($stageOrder[$stage] ?? 0) >= 6 ? ($loanApprovedDate ?? $leadDate)->addDays(random_int(2, 10)) : null;
        $completedDate = ($stageOrder[$stage] ?? 0) >= 7 ? ($legalProcessingDate ?? $leadDate)->addDays(random_int(7, 35)) : null;
        $commissionPaidDate = ($stageOrder[$stage] ?? 0) >= 8 ? ($completedDate ?? $leadDate)->addDays(random_int(3, 30)) : null;

        $salespersonId = User::role([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->inRandomOrder()->value('id');

        $leaderId = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->inRandomOrder()->value('id');

        $loanOfficerId = ($stageOrder[$stage] ?? 0) >= 4
            ? User::role(RoleEnum::LOAN_OFFICER->value)->inRandomOrder()->value('id')
            : null;

        $legalOfficerId = ($stageOrder[$stage] ?? 0) >= 6
            ? User::role(RoleEnum::LEGAL_OFFICER->value)->inRandomOrder()->value('id')
            : null;

        return [
            'deal_id' => null,
            'lead_id' => Lead::query()->inRandomOrder()->value('id') ?? Lead::factory(),
            'project_name' => $this->faker->city().' '.
                $this->faker->randomElement(['Residences', 'Tower', 'Heights', 'Gardens']),
            'developer' => $this->faker->optional()->company(),
            'unit_number' => $this->faker->optional()->bothify('##-##-###'),
            'selling_price' => $sellingPrice,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => round(($sellingPrice * $commissionPercentage) / 100, 2),
            'salesperson_id' => $salespersonId ?? User::factory(),
            'leader_id' => $leaderId ?? User::factory(),
            'loan_officer_id' => $loanOfficerId,
            'legal_officer_id' => $legalOfficerId,
            'booking_fee' => ($stageOrder[$stage] ?? 0) >= 2 ? $this->faker->randomFloat(2, 1000, 20000) : null,
            'spa_date' => $spaSignedDate?->toDateString(),
            'deal_closing_date' => $completedDate?->toDateString(),
            'pipeline' => $stage,
            'lead_date' => $leadDate,
            'viewing_date' => $viewingDate,
            'booking_date' => $bookingDate,
            'spa_signed_date' => $spaSignedDate,
            'loan_submitted_date' => $loanSubmittedDate,
            'loan_approved_date' => $loanApprovedDate,
            'legal_processing_date' => $legalProcessingDate,
            'completed_date' => $completedDate,
            'commission_paid_date' => $commissionPaidDate,
            'created_at' => $createdAt,
            'updated_at' => $commissionPaidDate ?? $completedDate ?? $loanApprovedDate ?? $loanSubmittedDate ?? $spaSignedDate ?? $bookingDate ?? $viewingDate ?? $leadDate,
        ];
    }
}
