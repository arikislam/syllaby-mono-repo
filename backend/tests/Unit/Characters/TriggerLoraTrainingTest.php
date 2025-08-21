<?php

namespace Tests\Unit\Characters;

// no Mockery needed for success-path test
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Characters\Jobs\TriggerLoraTraining;
use App\Syllaby\Characters\Notifications\CharacterGenerationFailedNotification;

uses(RefreshDatabase::class);

it('marks character as model training failed and refunds credits when job fails', function () {
    Notification::fake();

    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 100,
        'remaining_credit_amount' => 100,
    ]);

    $character = Character::factory()->for($user)->modelTraining()->create();

    app(CreditService::class)->setUser($user)->decrement(CreditEventEnum::CUSTOM_CHARACTER_PURCHASED, $character);

    expect($user->fresh()->remaining_credit_amount)->toBe(50);

    $job = new TriggerLoraTraining($character);

    $job->failed(new Exception('simulated failure'));

    $character->refresh();
    $user->refresh();

    expect($character->status)->toBe(CharacterStatus::MODEL_TRAINING_FAILED)
        ->and($user->remaining_credit_amount)->toBe(100);

    Notification::assertSentTo($user, CharacterGenerationFailedNotification::class);

    $this->assertDatabaseHas('credit_histories', [
        'user_id' => $user->id,
        'creditable_id' => $character->id,
        'description' => CreditEventEnum::REFUNDED_CREDITS->value,
    ]);
});

it('marks character as model training and saves provider id on success', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['owner' => 'syllaby-ai', 'name' => 'uuid']) // Mock model creation
            ->push(['id' => 'replicate-training-id']), // Mock training initiation
    ]);

    $user = User::factory()->create();
    $character = Character::factory()->for($user)->ready()->create();

    Media::factory(10)->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'collection_name' => 'poses',
    ]);

    $job = new class($character) extends TriggerLoraTraining
    {
        protected function createZipFile(): string
        {
            return 'https://example.com/fake-zip-file.zip';
        }
    };

    $job->handle();

    $character->refresh();

    expect($character->status)->toBe(CharacterStatus::MODEL_TRAINING)
        ->and($character->provider_id)->toBe('replicate-training-id')
        ->and($character->training_images)->toBe(10);
});
