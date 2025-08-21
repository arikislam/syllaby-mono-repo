<?php

namespace Tests\Feature\Speeches;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Speeches\Voice;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can fetch a list of all available tts voices', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $john = User::factory()->create();

    Voice::factory()->count(4)->state(new Sequence(
        ['user_id' => null, 'type' => Voice::STANDARD, 'provider' => 'elevenlabs'],
        ['user_id' => null, 'type' => Voice::STANDARD, 'provider' => 'elevenlabs'],
        ['user_id' => $user->id, 'type' => Voice::REAL_CLONE, 'provider' => 'elevenlabs'],
        ['user_id' => $john->id, 'type' => Voice::REAL_CLONE, 'provider' => 'elevenlabs'],
    ))->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/speeches/voices');

    $response->assertOk();

    $this->assertCount(3, $response->json('data'));
});
