<?php

namespace Tests\Unit\Characters;

use Event;
use App\Syllaby\Users\User;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\FakeStreamWrapper;
use Database\Seeders\CreditEventTableSeeder;
use Illuminate\Support\Facades\Notification;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Characters\Enums\CharacterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Characters\Jobs\TriggerLoraTraining;
use App\Syllaby\Characters\Jobs\HandlePoseTrainingWebhook;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use App\Syllaby\Characters\Notifications\CharacterGenerationFailedNotification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('processes pose training webhook and queues lora training', function () {
    Queue::fake();

    stream_wrapper_unregister('https');

    stream_wrapper_register('https', FakeStreamWrapper::class);

    $file = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $user = User::factory()->create();

    $character = Character::factory()->for($user)->poseGenerating()->create();

    $payload = [
        'status' => 'succeeded',
        'output' => array_fill(0, 10, 'https://example.com/pose.jpg'),
    ];

    (new HandlePoseTrainingWebhook($character, $payload))->handle();

    $character->refresh();

    expect($character->status)->toBe(CharacterStatus::POSE_READY)
        ->and($character->getMedia('poses')->count())->toBe(10);

    Queue::assertPushed(TriggerLoraTraining::class);
});

it('marks character as pose failed and refunds credits on webhook failure', function () {
    Notification::fake();

    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 100,
        'remaining_credit_amount' => 100,
    ]);

    $character = Character::factory()->for($user)->poseGenerating()->create();

    app(CreditService::class)
        ->setUser($user)
        ->decrement(CreditEventEnum::CUSTOM_CHARACTER_PURCHASED, $character);

    $job = new HandlePoseTrainingWebhook($character, ['status' => 'failed']);

    $job->failed(new \Exception('simulated failure'));

    $character->refresh();
    $user->refresh();

    expect($character->status)->toBe(CharacterStatus::POSE_FAILED)
        ->and($user->remaining_credit_amount)->toBe(100);

    $this->assertDatabaseHas('credit_histories', [
        'user_id' => $user->id,
        'creditable_id' => $character->id,
        'description' => CreditEventEnum::REFUNDED_CREDITS->value,
    ]);

    Notification::assertSentTo($user, CharacterGenerationFailedNotification::class);
});
