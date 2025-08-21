<?php

namespace Tests\Feature\Publisher\Publication;

use Event;
use Feature;
use Tests\TestCase;
use App\Syllaby\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('can associate thumbnails with publications', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $publication = $user->publications()->create();

    $this->actingAs($user, 'sanctum')->postJson("v1/publications/$publication->id/thumbnail", [
        'provider' => 'youtube',
        'files' => [
            $file = UploadedFile::fake()->image('image.jpg')->size(100)
        ]
    ])->assertCreated();

    $this->assertDatabaseCount('media', 1);

    $media = $publication->media->first();

    $this->assertTrue(Storage::disk('spaces')->exists($media->getPathRelativeToRoot()));

    $this->assertEquals($file->name, $media->file_name);
});

it('only keep last uploaded thumbnail and discard others', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $publication = $user->publications()->create();

    $publication->addMedia(UploadedFile::fake()->image('image.jpg')->size(100))->toMediaCollection('youtube-thumbnail');

    $this->assertDatabaseCount('media', 1);

    $this->actingAs($user, 'sanctum')->postJson("v1/publications/$publication->id/thumbnail", [
        'provider' => 'youtube',
        'files' => [
            UploadedFile::fake()->create('thumbnail.jpg', 100)
        ]
    ])->assertCreated();

    $this->assertDatabaseCount('media', 1);
});

it('can delete thumbnails from publication', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $publication = $user->publications()->create();

    $publication->addMedia(UploadedFile::fake()->image('image.jpg')->size(100))->toMediaCollection('youtube-thumbnail');

    $this->assertDatabaseCount('media', 1);

    $this->actingAs($user, 'sanctum')->deleteJson("v1/publications/$publication->id/thumbnail?provider=youtube")->assertNoContent();

    $this->assertDatabaseCount('media', 0);
});

test('provider is required for associating thumbnails with publication', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $publication = $user->publications()->create();

    $this->actingAs($user, 'sanctum')->postJson("v1/publications/$publication->id/thumbnail", [
        'files' => [
            UploadedFile::fake()->create('image.jpg', 100)
        ]
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors('provider');
});

test('provider is required for deleting thumbnails from publication', function () {
    $user = User::factory()->create();

    $publication = $user->publications()->create();

    $this->actingAs($user, 'sanctum')->deleteJson("v1/publications/$publication->id/thumbnail")
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors('provider');
});
