<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const TOTAL_USERS = 20;

    private const ADMIN_COUNT = 1;

    private const LEADER_COUNT = 5;

    private const LOAN_OFFICER_COUNT = 3;

    private const LEGAL_OFFICER_COUNT = 2;

    private Generator $faker;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $this->faker = app(Generator::class);

        $specialAccounts = [
            ['role' => RoleEnum::ADMIN->value, 'email' => 'admin@example.com', 'name' => 'Admin'],
            ['role' => RoleEnum::LEADER->value, 'email' => 'leader@example.com', 'name' => 'Leader'],
            ['role' => RoleEnum::SALESPERSON->value, 'email' => 'salesperson@example.com', 'name' => 'Salesperson', 'leader_id' => 2],
            ['role' => RoleEnum::LOAN_OFFICER->value, 'email' => 'loanofficer@example.com', 'name' => 'Loan Officer'],
            ['role' => RoleEnum::LEGAL_OFFICER->value, 'email' => 'legalofficer@example.com', 'name' => 'Legal Officer'],
        ];

        $specialUsers = collect();
        foreach ($specialAccounts as $account) {
            $special = User::factory()->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $password,
                'leader_id' => $account['leader_id'] ?? null,
            ]);
            $special->syncRoles([$account['role']]);
            if (in_array($account['role'], [RoleEnum::ADMIN->value, RoleEnum::LEADER->value], true)) {
                $special->forceFill(['leader_id' => $special->id])->saveQuietly();
            }
            $this->stampTimestamps($special);
            $specialUsers->push($special);
        }

        $admins = $this->createUsersByRole(
            role: RoleEnum::ADMIN->value,
            count: max(0, self::ADMIN_COUNT - 1),
            password: $password,
            faker: $this->faker
        );

        $leaders = $this->createUsersByRole(
            role: RoleEnum::LEADER->value,
            count: max(0, self::LEADER_COUNT - 1),
            password: $password,
            faker: $this->faker
        );

        $this->createUsersByRole(
            role: RoleEnum::LOAN_OFFICER->value,
            count: max(0, self::LOAN_OFFICER_COUNT - 1),
            password: $password,
            faker: $this->faker
        );

        $this->createUsersByRole(
            role: RoleEnum::LEGAL_OFFICER->value,
            count: max(0, self::LEGAL_OFFICER_COUNT - 1),
            password: $password,
            faker: $this->faker
        );

        $salespersonCount = self::TOTAL_USERS
            - self::ADMIN_COUNT
            - self::LEADER_COUNT
            - self::LOAN_OFFICER_COUNT
            - self::LEGAL_OFFICER_COUNT;
        $salespersonCount = max(0, $salespersonCount - 1);

        $reportingPool = $leaders
            ->merge($admins)
            ->merge(
                $specialUsers->filter(fn (User $user) => $user->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LEADER->value]))
            )
            ->values();
        for ($i = 1; $i <= $salespersonCount; $i++) {
            /** @var User $leader */
            $leader = $reportingPool->random();

            $salesperson = User::factory()->create([
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'password' => $password,
                'leader_id' => $leader->id,
            ]);

            $salesperson->syncRoles([RoleEnum::SALESPERSON->value]);
            $this->stampTimestamps($salesperson);
        }
    }

    private function createUsersByRole(
        string $role,
        int $count,
        string $password,
        Generator $faker
    ): Collection {
        $users = collect();

        for ($i = 1; $i <= $count; $i++) {
            $user = User::factory()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $password,
                'leader_id' => null,
            ]);

            $user->syncRoles([$role]);
            if (in_array($role, [RoleEnum::ADMIN->value, RoleEnum::LEADER->value], true)) {
                $user->forceFill(['leader_id' => $user->id])->saveQuietly();
            }
            $this->stampTimestamps($user);

            $users->push($user);
        }

        return $users;
    }

    private function stampTimestamps(User $user): void
    {
        $createdAt = $this->randomSeedDate();
        $user->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(random_int(0, 20)),
        ])->saveQuietly();
    }

    private function randomSeedDate(): Carbon
    {
        $now = now();
        $monthPool = array_values(array_unique([
            max(1, $now->month - 2),
            max(1, $now->month - 1),
            $now->month,
        ]));

        $month = (int) $monthPool[array_rand($monthPool)];
        $start = Carbon::create($now->year, $month, 1, 8, 0, 0);
        $end = $month === (int) $now->month
            ? $now->copy()
            : $start->copy()->endOfMonth()->setTime(20, 59, 59);

        return Carbon::instance($this->faker->dateTimeBetween($start, $end));
    }
}
