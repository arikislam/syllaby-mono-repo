<?php

namespace Tests\Feature\Publisher\Metadata;

use Tests\TestCase;
use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

it('can generate a tags for youtube', function () {
    Chat::fake();

    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('v1/metadata/generate/tags', [
        'context' => 'macbook are good',
        'provider' => 'youtube',
    ])->assertOk();

    expect($response->json('data'))->response->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE);
});
