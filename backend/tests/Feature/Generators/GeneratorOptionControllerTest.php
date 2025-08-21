<?php

namespace Tests\Feature\Generators;

use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can display the options for creating content', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/generators/options');

    expect($response->json('data'))
        ->toHaveKey('styles')
        ->toHaveKey('languages')
        ->toHaveKey('tones')
        ->toHaveKey('avatar-durations')
        ->toHaveKey('faceless-durations');
});
