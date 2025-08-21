<?php

namespace Tests\Feature\Characters;

use Queue;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use Illuminate\Http\UploadedFile;
use App\Http\Responses\ErrorCode;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeStreamWrapper;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Jobs\GeneratePosesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    Event::fake([MediaHasBeenAddedEvent::class]);
});

it('can start character training', function () {
    Queue::fake();

    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    stream_wrapper_unregister('https');

    stream_wrapper_register('https', FakeStreamWrapper::class);

    $file = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $media = Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'sandbox',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'name',
            ],
        ]);

    stream_wrapper_restore('https');

    Queue::assertPushed(GeneratePosesJob::class, function ($job) use ($character) {
        return $job->character->is($character);
    });

    $character->refresh();
    $user->refresh();

    expect($character->status)->toBe(CharacterStatus::POSE_GENERATING)
        ->and($user->remaining_credit_amount)
        ->toBe($user->monthly_credit_amount - config('credit-engine.events.custom_character_purchased.min_amount'));
});

it('validates required preview_id field', function () {
    $user = User::factory()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'sandbox',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['preview_id']);
});

it('validates preview_id exists in users sandbox media', function () {
    $user = User::factory()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'default',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['preview_id']);
});

it('validates preview belongs to user and is in sandbox collection', function () {
    $user = User::factory()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => User::factory()->create()->id,
        'collection_name' => 'default',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['preview_id']);
});

it('prevents training other users characters', function () {
    $user = User::factory()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => User::factory()->create()->id,
        'collection_name' => 'default',
    ]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertForbidden();
});

it('prevents training system characters', function () {
    $user = User::factory()->create();

    $character = Character::factory()->createQuietly();

    $media = Media::factory()->create([
        'user_id' => $user->id,
        'collection_name' => 'default',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertForbidden();
});

it('returns 404 for non-existent character', function () {
    $user = User::factory()->create();

    $media = Media::factory()->create([
        'user_id' => $user->id,
        'collection_name' => 'default',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/v1/characters/999/train', [
            'preview_id' => $media->id,
        ])->assertNotFound();
});

it('requires authentication', function () {
    $character = Character::factory()->create();

    $this->postJson("/v1/characters/{$character->id}/train", [
        'preview_id' => 1,
    ])->assertUnauthorized();
});

it('can not start training without sufficient credits', function () {
    $user = User::factory()->withoutCredits()->create();

    $character = Character::factory()->previewReady()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'sandbox',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/train", [
            'preview_id' => $media->id,
        ])
        ->assertForbidden()
        ->assertJsonPath('message', 'You do not have enough credits to train this character.')
        ->assertJsonPath('error.code', ErrorCode::INSUFFICIENT_CREDITS->value);
});