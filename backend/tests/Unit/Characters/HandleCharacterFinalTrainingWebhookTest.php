<?php

namespace Tests\Unit\Characters;

use Exception;
use App\Syllaby\Users\User;
use App\Syllaby\Characters\Character;
use Database\Seeders\CreditEventTableSeeder;
use Illuminate\Support\Facades\Notification;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Characters\Enums\CharacterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Characters\Jobs\HandleCharacterFinalTrainingWebhook;
use App\Syllaby\Characters\Notifications\CharacterGenerationFailedNotification;
use App\Syllaby\Characters\Notifications\CharacterGenerationSucceededNotification;

uses(RefreshDatabase::class);

it('marks the character as ready after successful final training', function () {
    Notification::fake();

    $user = User::factory()->create();

    $character = Character::factory()->for($user)->modelTraining()->create();

    $payload = [
        'status' => 'succeeded',
        'input' => [
            'trigger_word' => 'test_trigger',
        ],
        'output' => [
            'version' => 'model-v1',
        ],
    ];

    (new HandleCharacterFinalTrainingWebhook($character, $payload))->handle();

    $character->refresh();

    expect($character->status)->toBe(CharacterStatus::READY)
        ->and($character->trigger)->toBe('test_trigger')
        ->and($character->model)->toBe('model-v1')
        ->and($character->provider_id)->toBeNull();

    Notification::assertSentTo($user, CharacterGenerationSucceededNotification::class);
});

it('marks character as model training failed on webhook failure', function () {
    Notification::fake();

    $user = User::factory()->create();

    $character = Character::factory()->for($user)->modelTraining()->create();

    $job = new HandleCharacterFinalTrainingWebhook($character, ['status' => 'failed']);

    $job->failed(new Exception('simulated failure'));

    $character->refresh();

    expect($character->status)->toBe(CharacterStatus::MODEL_TRAINING_FAILED);

    Notification::assertSentTo($user, CharacterGenerationFailedNotification::class);
});

it('refunds credits when final training fails', function () {
    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 100,
        'remaining_credit_amount' => 100,
    ]);

    $character = Character::factory()->for($user)->modelTraining()->create();

    app(CreditService::class)
        ->setUser($user)
        ->decrement(CreditEventEnum::CUSTOM_CHARACTER_PURCHASED, $character);

    expect($user->remaining_credit_amount)->toBe(50);

    $job = new HandleCharacterFinalTrainingWebhook($character, ['status' => 'failed']);

    $job->failed(new Exception('simulated failure'));

    expect($user->refresh()->remaining_credit_amount)->toBe(100);
});
