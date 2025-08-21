<?php

namespace Tests\Feature\RealClones;

use Event;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Http\UploadedFile;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Http;
use App\Http\Responses\ErrorCode as Code;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('allows to upload and create a photo avatar with D-ID', function () {
    Feature::define('video', true);

    Http::fake([
        'https://api.d-id.com/images/image-id' => Http::response([]),
        'https://api.d-id.com/images' => Http::response([
            'id' => 'image-id', 'faces' => [
                [
                    'face_occluded' => false,
                    'detect_confidence' => 99.77,
                    'face_occluded_confidence' => 91,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/real-clones/photo-avatars', [
        'name' => 'John Doe',
        'gender' => 'male',
        'provider' => RealCloneProvider::D_ID->value,
        'file' => UploadedFile::fake()->image('my-avatar.png', width: 800, height: 880),
    ]);

    $avatar = Avatar::where('type', Avatar::PHOTO)->first();

    $response->assertCreated();
    expect($response->json('data'))
        ->gender->toBe('male')
        ->id->toBe($avatar->id)
        ->user_id->toBe($user->id)
        ->type->toBe(Avatar::PHOTO)
        ->preview->toBe($avatar->getFirstMedia('photo-avatar')->getFullUrl());
});

it('fails to upload photo with no recognisable face with D-ID', function () {
    Feature::define('video', true);

    Http::fake([
        'https://api.d-id.com/images/image-id' => Http::response([]),
        'https://api.d-id.com/images' => Http::response([
            'id' => 'image-id', 'faces' => [],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/real-clones/photo-avatars', [
        'name' => 'John Doe',
        'gender' => 'male',
        'provider' => RealCloneProvider::D_ID->value,
        'file' => UploadedFile::fake()->image('my-avatar.png', width: 800, height: 880),
    ]);

    $response->assertUnprocessable();
});

it('fails to upload any file that is not an image', function () {
    Feature::define('video', true);

    Http::fake(['*' => Http::response()]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/real-clones/photo-avatars', [
        'name' => 'John Doe',
        'gender' => 'male',
        'provider' => RealCloneProvider::D_ID->value,
        'file' => UploadedFile::fake()->create('audio.mp3', 3000, 'audio/mpeg'),
    ]);

    $response->assertUnprocessable();
});

it('fails to upload and create a photo avatar with feature disabled', function () {
    Feature::define('video', false);

    Http::fake([
        'https://api.d-id.com/images/image-id' => Http::response([]),
        'https://api.d-id.com/images' => Http::response([
            'id' => 'image-id', 'faces' => [
                [
                    'face_occluded' => false,
                    'detect_confidence' => 99.77,
                    'face_occluded_confidence' => 91,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/real-clones/photo-avatars', [
        'name' => 'John Doe',
        'gender' => 'male',
        'provider' => RealCloneProvider::D_ID->value,
        'file' => UploadedFile::fake()->image('my-avatar.png', width: 800, height: 880),
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});
