<?php

namespace Tests\Unit\Characters;

use Exception;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Characters\Character;
use Database\Seeders\CreditEventTableSeeder;
use Illuminate\Support\Facades\Notification;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Jobs\GeneratePosesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Characters\Notifications\CharacterGenerationFailedNotification;

uses(RefreshDatabase::class);

it('marks the character as pose generating and saves the prediction identifier', function () {
    Http::fake([
        '*' => Http::response(['id' => 'test-prediction-id']),
    ]);

    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'collection_name' => 'preview',
        'file_name' => 'preview.jpg',
        'disk' => 'spaces',
        'conversions_disk' => 'spaces',
    ]);

    (new GeneratePosesJob($character))->handle();

    $character->refresh();

    expect($character->status)->toBe(CharacterStatus::POSE_GENERATING)
        ->and($character->provider_id)->toBe('test-prediction-id');
});

it('marks the character as pose failed and refunds credits when the job fails', function () {
    Notification::fake();
    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 100,
        'remaining_credit_amount' => 100,
    ]);

    $character = Character::factory()->for($user)->poseGenerating()->create();

    app(CreditService::class)->setUser($user)
        ->decrement(CreditEventEnum::CUSTOM_CHARACTER_PURCHASED, $character);

    expect($user->remaining_credit_amount)->toBe(50);

    $job = new GeneratePosesJob($character);
    $job->failed(new Exception);

    $character->refresh();
    $user->refresh();

    expect($character->status)->toBe(CharacterStatus::POSE_FAILED)
        ->and($user->remaining_credit_amount)->toBe(100);

    Notification::assertSentTo($user, CharacterGenerationFailedNotification::class);

    $this->assertDatabaseHas('credit_histories', [
        'user_id' => $user->id,
        'creditable_id' => $character->id,
        'description' => CreditEventEnum::REFUNDED_CREDITS->value,
    ]);
});
