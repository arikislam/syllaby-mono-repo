<?php

namespace App\Feature\Subscriptions;

use App\Syllaby\Users\User;
use App\Syllaby\Subscriptions\Plan;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('can list extra credits packages', function () {
    $user = User::factory()->create();

    $voiceCloneProduct = Plan::factory()->product()->create();
    Plan::factory()->price()->create([
        'type' => 'one_time',
        'meta' => ['type' => 'voice-clone'],
        'parent_id' => $voiceCloneProduct->id,
    ]);

    $extraCreditsProduct = Plan::factory()->product()->create();
    Plan::factory()->price()->create([
        'type' => 'one_time',
        'meta' => ['type' => 'extra-credits'],
        'parent_id' => $extraCreditsProduct->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/subscriptions/products/extra-credits');

    $response->assertJsonCount(1, 'data')->assertJsonFragment([
        'type' => 'one_time',
        'metadata' => ['type' => 'extra-credits'],
    ]);
});

test('can list voice clone product', function () {
    $user = User::factory()->create();

    $voiceCloneProduct = Plan::factory()->product()->create();
    Plan::factory()->price()->create([
        'type' => 'one_time',
        'meta' => ['type' => 'voice-clone'],
        'parent_id' => $voiceCloneProduct->id,
    ]);

    $extraCreditsProduct = Plan::factory()->product()->create();
    Plan::factory()->price()->create([
        'type' => 'one_time',
        'meta' => ['type' => 'extra-credits'],
        'parent_id' => $extraCreditsProduct->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/subscriptions/products/voice-clone');

    $response->assertJsonCount(1, 'data')->assertJsonFragment([
        'type' => 'one_time',
        'metadata' => ['type' => 'voice-clone'],
    ]);
});
