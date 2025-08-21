<?php

namespace Tests\Feature\Publisher\Metadata;

use Tests\TestCase;
use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can generate a title for youtube', function () {
    Chat::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('v1/metadata/generate/title', [
        'context' => 'macbook are good',
        'provider' => 'youtube',
    ])->assertOk();

    expect($response->json('data'))->response->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE);
});

it('can generate a title for tiktok', function () {
    Chat::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('v1/metadata/generate/title', [
        'context' => 'macbook are good',
        'provider' => 'tiktok',
    ])->assertOk();

    expect($response->json('data'))->response->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE);
});
