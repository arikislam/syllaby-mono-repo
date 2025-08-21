<?php

namespace App\Feature\Subscriptions;

use App\Syllaby\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can fetch current subscription details', function () {
    $user = User::factory()->create(['email' => 'john@syllaby.io']);
    $user->newSubscription('default', 'price_1MpUl5LHvIiL7GifPYjOF7lU')->create('pm_card_visa');

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/subscriptions/details');

    $response->assertJsonStructure([
        'data' => [
            'plan' => [
                'id',
                'name',
                'recurrence',
                'amount',
                'created_at',
                'canceled_at',
                'has_discount',
                'discount' => ['amount', 'ends_at'],
            ],
            'billing' => ['amount', 'next_payment'],
        ],
    ]);
})->skip();
