<?php

namespace Tests\Unit\Videos;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Actions\TimeoutVideoAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks video as failed when stuck for more than 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::RENDERING,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('does not mark video as failed when less than 24 hours old', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::RENDERING,
        'updated_at' => now()->subHours(23),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeFalse();
    expect($video->fresh()->status)->toBe(VideoStatus::RENDERING);
});

it('does not mark completed videos as failed', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::COMPLETED,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeFalse();
    expect($video->fresh()->status)->toBe(VideoStatus::COMPLETED);
});

it('does not mark already failed videos as failed', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::FAILED,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeFalse();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks draft videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::DRAFT,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks syncing videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::SYNCING,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks sync failed videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::SYNC_FAILED,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks timeout videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::TIMEOUT,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks modifying videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::MODIFYING,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});

it('marks modified videos as failed after 24 hours', function () {
    $video = Video::factory()->create([
        'status' => VideoStatus::MODIFIED,
        'updated_at' => now()->subHours(25),
    ]);

    $result = (new TimeoutVideoAction)->handle($video);

    expect($result)->toBeTrue();
    expect($video->fresh()->status)->toBe(VideoStatus::FAILED);
});
