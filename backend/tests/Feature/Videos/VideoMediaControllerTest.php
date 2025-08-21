<?php

namespace Tests\Feature\Videos;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use Tests\Stubs\CreatomateStub;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Metadata\Timeline;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use App\Http\Responses\ErrorCode as Code;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('can show video assets', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $file = UploadedFile::fake()->image('image.jpg');
    $video->addMedia($file)->toMediaCollection('assets');

    $this->actingAs($user);
    $response = $this->getJson("v1/videos/{$video->id}/assets")->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('can filter assets by type', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $audio = UploadedFile::fake()->create('audio.mp3', 10, 'audio/mpeg');
    $video->addMedia($audio)->toMediaCollection('assets');

    $image = UploadedFile::fake()->image('image.jpg');
    $video->addMedia($image)->toMediaCollection('assets');

    $this->actingAs($user);
    $response = $this->getJson("v1/videos/{$video->id}/assets?filter[type]=image")->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('upload assets for the given video', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $files = [
        UploadedFile::fake()->image('image-1.jpg')->size(20000),
        UploadedFile::fake()->image('image-2.jpg')->size(30000),
    ];

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/{$video->id}/assets", [
        'files' => $files,
    ]);

    $response->assertCreated();

    expect($response->json('data'))->toHaveCount(2)->sequence(
        fn ($media) => expect(Arr::get($media->value, 'file_name'))->toBe($files[0]->getClientOriginalName()),
        fn ($media) => expect(Arr::get($media->value, 'file_name'))->toBe($files[1]->getClientOriginalName()),
    );

    $this->assertDatabaseCount('media', 2);
    Storage::disk('spaces')->assertExists("/{$response->json('data.0.id')}/{$files[0]->getClientOriginalName()}");
    Storage::disk('spaces')->assertExists("/{$response->json('data.1.id')}/{$files[1]->getClientOriginalName()}");
});

it('fails uploading assets with insufficient storage', function () {
    Feature::define('max_storage', 1);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/{$video->id}/assets", [
        'files' => [UploadedFile::fake()->image('image.jpg')->size(200)],
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::REACH_PLAN_STORAGE_LIMIT->value,
    ]);
});

it('fails to upload assets to another user video', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $video = Video::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/{$video->id}/assets", [
        'file' => [UploadedFile::fake()->image('image.jpg')->size(200)],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('media', 0);
});

it('does not show assets from other users', function () {
    $video = Video::factory()->create();

    $file = UploadedFile::fake()->image('image.jpg');
    $video->addMedia($file)->toMediaCollection('assets');

    $this->actingAs(User::factory()->create());

    $this->getJson("v1/videos/{$video->id}/assets")->assertForbidden();
});

it('removes an asset and all its references from the video timeline - root level', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->recycle($user)->create();
    $video = $footage->video;

    $timeline = Timeline::factory()->for($footage, 'model')->create([
        'user_id' => $user->id,
        'content' => CreatomateStub::timeline(),
    ]);

    $file = UploadedFile::fake()->image('image.jpg');
    $media = $video->addMedia($file)->toMediaCollection('assets');

    $source = CreatomateStub::timeline();
    $source['elements'][] = [
        'id' => Str::uuid()->toString(),
        'name' => $media->uuid,
        'type' => 'image',
        'track' => 4,
        'time' => '0 s',
        'source' => $media->getFullUrl(),
    ];

    $timeline->update(['content' => $source]);

    expect($timeline->content['elements'])->toHaveCount(4);

    $this->actingAs($user);
    $response = $this->deleteJson("v1/videos/{$video->id}/assets/{$media->id}");
    $response->assertNoContent();

    $timeline = $timeline->refresh();
    expect($timeline->content['elements'])
        ->toHaveCount(3)
        ->not->toHaveKey('name', $media->uuid);

    $this->assertDatabaseCount('media', 0);
    Storage::disk('spaces')->assertMissing("/{$media->id}/{$file->getClientOriginalName()}");
});

it('removes an asset and all its references from the video timeline - nested', function () {
    $user = User::factory()->create();

    $footage = Footage::factory()->recycle($user)->create();
    $timeline = Timeline::factory()->for($footage, 'model')->create([
        'user_id' => $user->id,
    ]);

    $video = $footage->video;
    $file = UploadedFile::fake()->image('image.jpg');
    $media = $video->addMedia($file)->toMediaCollection('assets');

    $source = CreatomateStub::timeline();
    array_push($source['elements'], [
        'id' => Str::uuid()->toString(),
        'name' => $media->uuid,
        'type' => 'image',
        'track' => 4,
        'time' => 0,
        'source' => $media->getFullUrl(),
    ], [
        'id' => Str::uuid()->toString(),
        'name' => 'Composition One',
        'type' => 'composition',
        'track' => 4,
        'time' => 0,
        'elements' => [
            [
                'id' => Str::uuid()->toString(),
                'name' => $media->uuid,
                'type' => 'image',
                'track' => 4,
                'time' => 0,
                'source' => $media->getFullUrl(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Composition Two',
                'type' => 'composition',
                'track' => 4,
                'time' => 0,
                'elements' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'name' => $media->uuid,
                        'type' => 'image',
                        'track' => 4,
                        'time' => 0,
                        'source' => $media->getFullUrl(),
                    ],
                ],
            ],
        ],
    ], [
        'id' => Str::uuid()->toString(),
        'name' => 'Composition Three',
        'type' => 'composition',
        'track' => 4,
        'time' => 0,
        'elements' => [
            [
                'id' => Str::uuid()->toString(),
                'name' => $media->uuid,
                'type' => 'image',
                'track' => 4,
                'time' => 0,
                'source' => $media->getFullUrl(),
            ],
            [
                'id' => '81a61c87-6278-4f91-b3ab-070bf9aa16dc',
                'name' => 'Text 1',
                'type' => 'text',
                'track' => 2,
                'time' => 0,
                'duration' => 1.5,
                'width' => '59.2784%',
                'height' => '23.4107%',
                'fill_color' => '#333333',
                'text' => 'Heading',
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Composition Four',
                'type' => 'composition',
                'track' => 4,
                'time' => 0,
                'elements' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'name' => $media->uuid,
                        'type' => 'image',
                        'track' => 4,
                        'time' => 0,
                        'source' => $media->getFullUrl(),
                    ],
                ],
            ],
        ],
    ]);

    $timeline->update(['content' => $source]);

    expect($timeline->content['elements'])->toHaveCount(6);

    $this->actingAs($user);
    $response = $this->deleteJson("v1/videos/{$video->id}/assets/{$media->id}");
    $response->assertNoContent();

    $timeline = $timeline->refresh();
    expect($timeline->content['elements'])->not->toHaveKey('url', $media->getFullUrl())
        ->and($timeline->content['elements'][4]['elements'])->not->toHaveKey('url', $media->getFullUrl());

    $this->assertDatabaseCount('media', 0);
    Storage::disk('spaces')->assertMissing("/{$media->id}/{$file->getClientOriginalName()}");
});

it('fails to remove an asset from another user video', function () {
    $footage = Footage::factory()->create();
    $video = $footage->video;

    $file = UploadedFile::fake()->image('image.jpg');
    $media = $video->addMedia($file)->toMediaCollection('assets');

    $this->actingAs(User::factory()->create());
    $this->deleteJson("v1/videos/{$video->id}/assets/{$media->id}")->assertForbidden();

    $this->assertDatabaseCount('media', 1);
    Storage::disk('spaces')->exists("/{$media->id}/{$file->getClientOriginalName()}");
});
