<?php

namespace Tests\Feature\Characters;

use Http;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeStreamWrapper;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    Event::fake([MediaHasBeenAddedEvent::class]);
});

it('can create a character preview', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'test-prediction-id'])
            ->push([
                'id' => 'test-prediction-id',
                'status' => 'succeeded',
                'output' => [
                    'url' => 'https://example.com/preview.jpg',
                ],
            ]),
    ]);

    stream_wrapper_unregister('https');

    stream_wrapper_register('https', FakeStreamWrapper::class);

    $file = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->create();

    $character = Character::factory()->for($user)->create();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $character->id)
        ->assertJsonPath('data.preview.0.model_id', $character->id)
        ->assertJsonPath('data.preview.0.model_type', $character->getMorphClass())
        ->assertJsonPath('data.preview.0.collection', 'sandbox');

    $this->assertDatabaseHas('media', [
        'model_id' => $character->id,
        'model_type' => $character->getMorphClass(),
        'collection_name' => 'sandbox',
        'name' => 'preview',
    ]);

    stream_wrapper_restore('https');
});

it('validates required genre_id field', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'image_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['genre_id']);
});

it('validates required image_id field', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->active()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image_id']);
});

it('validates genre_id exists', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => 999,
            'image_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['genre_id']);
});

it('validates image_id exists in users reference media', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => 999,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image_id']);
});

it('validates image belongs to user and is in reference collection', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $character = Character::factory()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => User::factory()->create()->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image_id']);
});

it('validates image is in reference collection', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $character = Character::factory()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => User::factory()->create()->id,
        'collection_name' => 'sandbox',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image_id']);
});

it('prevents creating preview for other users characters', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $character = Character::factory()->for($user)->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertForbidden();
});

it('prevents creating preview for system characters', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $character = Character::factory()->createQuietly();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => null,
        'collection_name' => 'default',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/preview", [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertForbidden();
});

it('returns 404 for non-existent character', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->active()->create();

    $media = Media::factory()->create([
        'model_id' => $user->id,
        'model_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'collection_name' => 'reference',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/v1/characters/999/preview', [
            'genre_id' => $genre->id,
            'image_id' => $media->id,
        ])
        ->assertNotFound();
});

it('requires authentication', function () {
    $character = Character::factory()->create();

    $genre = Genre::factory()->create();

    $this->postJson("/v1/characters/{$character->id}/preview", [
        'genre_id' => $genre->id,
        'image_id' => 1,
    ])->assertUnauthorized();
});
