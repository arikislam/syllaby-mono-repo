<?php

namespace Tests\Feature\Videos;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeStreamWrapper;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Events\VideoModified;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('video status changes to modifying when asset is processing', function () {
    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->completed()->create();

    $faceless = Faceless::factory()->recycle($user)->for($video)->create();

    $asset = Asset::factory()->aiVideo()->processing()->recycle($user)->create();

    $faceless->assets()->attach($asset->id, [
        'order' => 0,
        'active' => true,
    ]);

    event(new VideoModified($video));

    expect($video->fresh()->status)->toBe(VideoStatus::MODIFYING);
});

test('video status changes to modified when all assets are completed', function () {
    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->modifying()->create();

    $faceless = Faceless::factory()->recycle($user)->for($video)->create();

    $assets = Asset::factory()->aiVideo()->success()->recycle($user)->create();

    $faceless->assets()->attach($assets->id, [
        'order' => 0,
        'active' => true,
    ]);

    event(new VideoModified($video));

    expect($video->fresh()->status)->toBe(VideoStatus::MODIFIED);
});

test('video modification status is triggered by minimax webhook', function () {
    Event::fake();

    Http::fake([
        'https://api.minimaxi.chat/v1/*' => Http::response([
            'base_resp' => ['status_code' => 0],
            'file' => ['download_url' => 'https://example.com'],
        ]),
    ]);

    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->create(['status' => VideoStatus::MODIFYING]);

    $faceless = Faceless::factory()->recycle($user)->create(['video_id' => $video->id]);

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    $faceless->assets()->attach($asset->id, [
        'order' => 0,
        'active' => true,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 0],
        'status' => 'success',
        'file_id' => 12345678,
    ])->assertOk();

    Event::assertDispatched(VideoModified::class);
})->skip('Taking too long to run - Trying to download animation');

test('video modification status is triggered by asset attachment', function () {
    Event::fake();

    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->completed()->create();

    $faceless = Faceless::factory()->recycle($user)->for($video)->create();

    $asset = Asset::factory()->recycle($user)->create();

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset->id,
        'index' => 0,
    ])->assertOk();

    Event::assertDispatched(VideoModified::class);
});

test('video modification status is triggered by media upload', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Event::fake([VideoModified::class, MediaHasBeenAddedEvent::class]);

    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->completed()->create();

    $faceless = Faceless::factory()->recycle($user)->for($video)->create();

    $file = UploadedFile::fake()->create('video.mp4', 1000);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/upload", [
        'file' => $file,
        'index' => 0,
    ])->assertOk();

    Event::assertDispatched(VideoModified::class);
});

test('video modification status is triggered by transload upload', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Event::fake([VideoModified::class, MediaHasBeenAddedEvent::class]);

    Http::fake([
        'https://example.com/stock.jpg' => Http::response(null, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => 1000,
        ]),
    ]);

    $user = User::factory()->create();

    $video = Video::factory()->recycle($user)->completed()->create();

    $faceless = Faceless::factory()->recycle($user)->for($video)->create();

    $image = UploadedFile::fake()->image('image.jpg');

    stream_wrapper_unregister('https');

    stream_wrapper_register('https', FakeStreamWrapper::class);

    FakeStreamWrapper::$content = file_get_contents($image->getRealPath());

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/transload", [
        'url' => 'https://example.com/stock.jpg',
        'index' => 0,
        'type' => 'image/jpeg',
    ])->assertOk();

    stream_wrapper_restore('https');

    Event::assertDispatched(VideoModified::class);
});
