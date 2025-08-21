<?php

namespace Tests\Feature\Characters;

use App\Syllaby\Users\User;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Characters\Jobs\HandlePoseTrainingWebhook;
use App\Syllaby\Characters\Jobs\HandleCharacterFinalTrainingWebhook;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('dispatches pose training job when pose webhook succeeds', function () {
    $character = Character::factory()->for(User::factory()->create())->create();

    $this->postJson("/custom-character/webhook/poses?character={$character->id}", [
        'status' => 'succeeded',
        'output' => [],
    ])->assertOk();

    Queue::assertPushed(HandlePoseTrainingWebhook::class);
});

it('does not dispatch any job when pose webhook is still processing', function () {
    $character = Character::factory()->for(User::factory()->create())->create();

    $this->postJson("/custom-character/webhook/poses?character={$character->id}", [
        'status' => 'processing',
    ])->assertOk();

    Queue::assertNothingPushed();
});

it('dispatches final training job when final webhook succeeds', function () {
    $character = Character::factory()->for(User::factory()->create())->create();

    $this->postJson("/custom-character/webhook/final?character={$character->id}", [
        'status' => 'succeeded',
        'output' => [],
    ])->assertOk();

    Queue::assertPushed(HandleCharacterFinalTrainingWebhook::class);
});

it('returns success without dispatching when character is missing', function () {
    $this->postJson('/custom-character/webhook/poses', [
        'status' => 'succeeded',
        'output' => [],
    ])->assertOk();

    Queue::assertNothingPushed();
});
