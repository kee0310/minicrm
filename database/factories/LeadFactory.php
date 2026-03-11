<?php

namespace Database\Factories;

use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
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
        $salespersonId = User::role([
            RoleEnum::SALESPERSON->value,
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->inRandomOrder()->value('id');

        $leaderId = User::role([
            RoleEnum::LEADER->value,
            RoleEnum::ADMIN->value,
        ])->inRandomOrder()->value('id');

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('01#########'),
            'source' => $this->faker->randomElement(['Facebook', 'Friend Referral', 'Exhibition/Fair', 'Company Assigned', 'Old Client Referral']),
            'salesperson_id' => $salespersonId ?? User::factory(),
            'leader_id' => $leaderId,
            'status' => $this->faker->randomElement(LeadStatusEnum::values()),
        ];
    }
}
