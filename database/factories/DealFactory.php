<?php

namespace Database\Factories;

use App\Enums\LeadStatusEnum;
use App\Enums\PipelineEnum;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
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
        $bookingFee = $this->faker->boolean(70) ? $this->faker->randomFloat(2, 1000, 20000) : null;
        $spaDate = $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-6 months', 'now') : null;
        $closingDate = $spaDate ? $this->faker->dateTimeBetween($spaDate, '+6 months') : null;

        return [
            'deal_id' => null,
            'lead_id' => Lead::query()->where('status', LeadStatusEnum::DEAL->value)->inRandomOrder()->value('id')
                ?? Lead::factory()->state(['status' => LeadStatusEnum::DEAL->value]),
            'project_name' => $this->faker->words(3, true),
            'developer' => $this->faker->optional()->company(),
            'unit_number' => $this->faker->optional()->bothify('##-##-###'),
            'selling_price' => $sellingPrice,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => round(($sellingPrice * $commissionPercentage) / 100, 2),
            'salesperson_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'leader_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'booking_fee' => $bookingFee,
            'spa_date' => $spaDate?->format('Y-m-d'),
            'deal_closing_date' => $closingDate?->format('Y-m-d'),
            'pipeline' => $this->faker->randomElement(PipelineEnum::values()),
        ];
    }
}
