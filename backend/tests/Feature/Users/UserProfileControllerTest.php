<?php

namespace Tests\Feature\Users;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows that user can view his profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/user/me')
        ->assertOk();

    expect($response->json('data'))->toHaveKeys([
        'id',
        'name',
        'email',
        'settings',
        'notifications',
        'credits',
    ]);
});

it('shows that user can update his email and name', function () {
    $user = User::factory()->create(['name' => 'Sharryy', 'email' => 'sharryy@syllaby.io']);

    $this->assertEquals('Sharryy', $user->name);
    $this->assertEquals('sharryy@syllaby.io', $user->email);

    $response = $this->actingAs($user, 'sanctum')->patchJson('v1/user/profile', [
        'name' => 'Helder Lucas',
        'email' => 'helder@syllaby.io',
    ])->assertOk();

    expect($response->json('data'))->toHaveKeys([
        'id',
        'name',
        'email',
        'settings',
        'notifications',
        'credits',
    ]);

    $user->refresh();

    $this->assertEquals('Helder Lucas', $user->name);
    $this->assertEquals('helder@syllaby.io', $user->email);
});
