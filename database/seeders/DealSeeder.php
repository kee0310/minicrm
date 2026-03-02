<?php

namespace Database\Seeders;

use App\Enums\LeadStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $clients = Client::query()->get();

    if ($clients->isEmpty()) {
      Lead::factory(20)->create(['status' => LeadStatusEnum::DEAL->value]);
      $clients = Client::query()->get();
    }

    $salespeople = User::role([
      RoleEnum::SALESPERSON->value,
      RoleEnum::LEADER->value,
      RoleEnum::ADMIN->value,
    ])->get();

    $leaders = User::role([
      RoleEnum::LEADER->value,
      RoleEnum::ADMIN->value,
    ])->get();

    if ($clients->isEmpty() || $salespeople->isEmpty() || $leaders->isEmpty()) {
      return;
    }

    $faker = fake();
    $pipelines = [
      PipelineEnum::NEW->value,
      PipelineEnum::VIEWING->value,
      PipelineEnum::BOOKING->value,
      PipelineEnum::SPA_SIGNED->value,
      PipelineEnum::LOAN_SUBMITTED->value,
      PipelineEnum::LOAN_APPROVED->value,
      PipelineEnum::LEGAL_PROCESSING->value,
      PipelineEnum::COMPLETED->value,
      PipelineEnum::COMMISSION_PAID->value,
      PipelineEnum::BOOKING->value,
      PipelineEnum::SPA_SIGNED->value,
      PipelineEnum::LOAN_APPROVED->value,
      PipelineEnum::COMPLETED->value,
    ];

    for ($i = 0; $i < 95; $i++) {
      /** @var Client $client */
      $client = $clients->random();

      /** @var User $salesperson */
      $salesperson = $client->salesperson_id
        ? $salespeople->firstWhere('id', $client->salesperson_id) ?? $salespeople->random()
        : $salespeople->random();

      $leaderId = $salesperson->leader_id;
      if ($salesperson->hasAnyRole([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])) {
        $leaderId = $salesperson->id;
      } elseif (!$leaderId) {
        $leaderId = $leaders->random()->id;
      }

      $pipeline = $pipelines[array_rand($pipelines)];
      $createdAt = Carbon::now()->subDays(random_int(0, 240))->setTime(random_int(8, 20), random_int(0, 59));

      $sellingPrice = $faker->randomFloat(2, 150000, 2500000);
      $commissionPercentage = $faker->randomFloat(2, 1.0, 4.0);
      $spaDate = in_array($pipeline, [
        PipelineEnum::SPA_SIGNED->value,
        PipelineEnum::LOAN_SUBMITTED->value,
        PipelineEnum::LOAN_APPROVED->value,
        PipelineEnum::LEGAL_PROCESSING->value,
        PipelineEnum::COMPLETED->value,
        PipelineEnum::COMMISSION_PAID->value,
      ], true) ? $createdAt->copy()->addDays(random_int(10, 45)) : null;

      $closingDate = in_array($pipeline, [
        PipelineEnum::COMPLETED->value,
        PipelineEnum::COMMISSION_PAID->value,
      ], true) && $spaDate
        ? $spaDate->copy()->addDays(random_int(20, 90))
        : null;

      $deal = Deal::query()->create([
        'client_id' => $client->id,
        'project_name' => $faker->city() . ' ' . $faker->randomElement([
          'Residences',
          'Tower',
          'Heights',
          'Gardens',
          'Sentral',
        ]),
        'developer' => $faker->company(),
        'unit_number' => $faker->bothify('##-##-###'),
        'selling_price' => $sellingPrice,
        'commission_percentage' => $commissionPercentage,
        'salesperson_id' => $salesperson->id,
        'leader_id' => $leaderId,
        'booking_fee' => in_array($pipeline, [
          PipelineEnum::BOOKING->value,
          PipelineEnum::SPA_SIGNED->value,
          PipelineEnum::LOAN_SUBMITTED->value,
          PipelineEnum::LOAN_APPROVED->value,
          PipelineEnum::LEGAL_PROCESSING->value,
          PipelineEnum::COMPLETED->value,
          PipelineEnum::COMMISSION_PAID->value,
        ], true) ? $faker->randomFloat(2, 3000, 20000) : null,
        'spa_date' => $spaDate?->toDateString(),
        'deal_closing_date' => $closingDate?->toDateString(),
        'pipeline' => $pipeline,
      ]);

      $deal->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt->copy()->addDays(random_int(0, 30)),
      ])->saveQuietly();

      if (is_null($client->monthly_income)) {
        $client->forceFill([
          'monthly_income' => $faker->randomFloat(2, 3500, 25000),
          'occupation' => $faker->jobTitle(),
          'company' => $faker->company(),
          'age' => random_int(24, 58),
        ])->saveQuietly();
      }
    }
  }
}

