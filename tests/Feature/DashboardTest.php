<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    Role::findOrCreate('Leader');
    $user = User::factory()->leader()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard.index'));
    $response->assertOk();
});
