<?php

namespace Database\Factories;

use App\Enums\LeadStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'source' => $this->faker->randomElement(['Facebook', 'Friend Referral', 'Exhibition/Fair', 'Company Assigned', 'Old Client Referral']),
            'salesperson_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'leader_id' => User::query()->inRandomOrder()->value('id'),
            'status' => $this->faker->randomElement(LeadStatusEnum::values()),
        ];
    }
}
