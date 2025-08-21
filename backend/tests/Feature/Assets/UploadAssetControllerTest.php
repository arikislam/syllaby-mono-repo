<?php

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeStreamWrapper;
use Illuminate\Support\Facades\Storage;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Storage::fake();
    Event::fake([MediaHasBeenAddedEvent::class]);

    Feature::define('max_storage', 5368709120);
});

it('can upload an image asset', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test.jpg');

    stream_wrapper_unregister('https');
    stream_wrapper_register('https', FakeStreamWrapper::class);
    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $this->streamWrapperRegistered = true;

    $response = $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
        'name' => 'Test Asset',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Test Asset')
        ->assertJsonPath('data.type', 'custom-image')
        ->assertJsonPath('data.status', 'success');

    $this->assertDatabaseHas('assets', [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
        'name' => 'Test Asset',
    ]);
});

it('can upload a video asset', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp4', 5000, 'video/mp4');

    stream_wrapper_unregister('https');
    stream_wrapper_register('https', FakeStreamWrapper::class);
    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $this->streamWrapperRegistered = true;

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
    ])
        ->assertOk()
        ->assertJsonPath('data.type', 'custom-video')
        ->assertJsonPath('data.name', 'test.mp4');
});

it('can upload asset with genre', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();
    $file = UploadedFile::fake()->image('test.jpg');

    stream_wrapper_unregister('https');
    stream_wrapper_register('https', FakeStreamWrapper::class);
    FakeStreamWrapper::$content = file_get_contents($file->getRealPath());

    $this->streamWrapperRegistered = true;

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
        'name' => 'Test Asset',
        'genre_id' => $genre->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.genre_id', $genre->id);
});

it('validates file is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'name' => 'Test Asset',
    ])
        ->assertForbidden()
        ->assertJson(['message' => 'A file is required']);
});

it('validates file type', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.txt', 100);

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates image file size', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test.jpg')->size(6000); // 6MB

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates video file size', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp4', 51000, 'video/mp4'); // 51MB

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates genre exists', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test.jpg');

    $this->actingAs($user)->postJson('/v1/assets/upload', [
        'file' => $file,
        'genre_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['genre_id']);
});

afterEach(function () {
    if (isset($this->streamWrapperRegistered) && $this->streamWrapperRegistered) {
        stream_wrapper_restore('https');
        $this->streamWrapperRegistered = false;
    }
});
