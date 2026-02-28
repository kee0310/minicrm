<?php

namespace Database\Seeders;

use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $clientIds = Client::query()->pluck('id');

    if ($clientIds->isEmpty()) {
      Lead::factory(10)->create(['status' => LeadStatusEnum::DEAL->value]);
      $clientIds = Client::query()->pluck('id');
    }

    $salespersonIds = User::role([
      RoleEnum::USER->value,
      RoleEnum::LEADER->value,
      RoleEnum::ADMIN->value,
    ])->pluck('id');

    $leaderIds = User::role([
      RoleEnum::LEADER->value,
      RoleEnum::ADMIN->value,
    ])->pluck('id');

    Deal::factory(10)->make()->each(function (Deal $deal) use ($clientIds, $salespersonIds, $leaderIds) {
      $deal->client_id = $clientIds->random();
      $deal->salesperson_id = $salespersonIds->random();
      $deal->leader_id = $leaderIds->random();
      $deal->save();
    });
  }
}

