<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Leads>
 */
class LeadsFactory extends Factory
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
            'assigned_to' => $this->faker->name(),
            'leader' => $this->faker->name(),
            'status' => $this->faker->randomElement(['New', 'Contacted', 'Scheduled', 'Deal', 'Lost']),
        ];
    }
}
