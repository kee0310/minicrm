<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    Role::findOrCreate('Leader', 'web');
    Role::findOrCreate('Salesperson', 'web');
    Role::findOrCreate('Admin', 'web');
    $user = User::factory()->leader()->create();
    $this->actingAs($user);
    if (DB::getDriverName() === 'sqlite') {
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$args) => max($args));
        DB::connection()->getPdo()->sqliteCreateFunction('DATEDIFF', function ($date1, $date2) {
            if ($date1 === null || $date2 === null) {
                return null;
            }
            try {
                $d1 = new \DateTimeImmutable((string) $date1);
                $d2 = new \DateTimeImmutable((string) $date2);
            } catch (\Exception) {
                return null;
            }
            return (int) $d1->diff($d2)->format('%r%a');
        });
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
            if ($date === null || $format === null) {
                return null;
            }
            try {
                $dt = new \DateTimeImmutable((string) $date);
            } catch (\Exception) {
                return null;
            }
            $phpFormat = str_replace(['%Y', '%m', '%d', '%H', '%i', '%s'], ['Y', 'm', 'd', 'H', 'i', 's'], (string) $format);
            return $dt->format($phpFormat);
        });
    }

    $response = $this->get(route('dashboard.index'));
    $response->assertOk();
});
