<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    Role::findOrCreate('Leader');
    $user = User::factory()->leader()->create();
    $this->actingAs($user);
    if (DB::getDriverName() === 'sqlite') {
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$args) => max($args));
    }

    $response = $this->get(route('dashboard.index'));
    $response->assertOk();
});
