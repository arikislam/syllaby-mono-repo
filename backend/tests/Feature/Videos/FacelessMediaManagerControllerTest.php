<?php

namespace Tests\Feature\Videos;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Trackers\Tracker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeStreamWrapper;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Assets\Enums\AssetProvider;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('uploads a custom image for a faceless video', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Storage::fake('spaces');

    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->ai()->create();
    $file = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/upload", [
        'index' => 2,
        'file' => $file,
    ])->assertOk();

    $asset = Asset::first();

    $this->assertDatabaseHas('assets', [
        'user_id' => $user->id,
        'type' => AssetType::CUSTOM_IMAGE,
        'status' => 'success',
        'genre_id' => $faceless->genre_id,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $asset->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 2,
    ]);

    $this->assertDatabaseHas('media', [
        'model_id' => $asset->id,
        'model_type' => $asset->getMorphClass(),
        'collection_name' => 'default',
    ]);

    Storage::disk('spaces')->assertExists("/{$asset->getFirstMedia()->id}/image.jpg");
});

it('uploads a custom video for a faceless video', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Storage::fake('spaces');

    $user = User::factory()->create();
    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/upload", [
        'index' => 3,
        'file' => UploadedFile::fake()->create('video.mp4', 500, 'video/mp4')->size(200),
    ])->assertOk();

    $asset = Asset::first();

    $this->assertDatabaseHas('assets', [
        'user_id' => $user->id,
        'type' => AssetType::CUSTOM_VIDEO,
        'status' => 'success',
        'genre_id' => $faceless->genre_id,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $asset->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 3,
    ]);

    $this->assertDatabaseHas('media', [
        'model_id' => $asset->id,
        'model_type' => $asset->getMorphClass(),
        'collection_name' => 'default',
    ]);

    Storage::disk('spaces')->assertExists("/{$asset->getFirstMedia()->id}/video.mp4");
});

it('generates an AI image for a faceless video', function () {
    Chat::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'gen-123', 'status' => 'processing'])
            ->push(['id' => 'gen-123', 'status' => 'succeeded', 'output' => ['https://example.com/image.jpg']]),
    ]);

    $image = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    stream_wrapper_unregister('https');

    stream_wrapper_register('https', FakeStreamWrapper::class);

    FakeStreamWrapper::$content = file_get_contents($image->getRealPath());

    $user = User::factory()->create();

    $initial = Asset::factory()->recycle($user)->withMedia()->create([
        'type' => AssetType::AI_IMAGE->value,
        'description' => 'AI generated description',
    ]);

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
        'meta' => [
            'model' => 'fake-owner/fake-name',
            'input' => ['prompt' => '[PROMPT]'],
        ],
    ]);

    $faceless = Faceless::factory()->ai()->recycle($user)->create(['genre_id' => $genre->id]);

    $faceless->assets()->attach($initial->id, ['order' => 0, 'active' => true]);

    $tracker = Tracker::factory()->for($faceless, 'trackable')->create([
        'user_id' => $user->id,
        'name' => 'image-generation',
    ]);

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/generate", [
        'index' => 0,
    ])->assertOk();

    stream_wrapper_restore('https');

    $asset = Asset::query()->orderByDesc('id')->first();

    expect($asset)
        ->user_id->toBe($user->id)
        ->type->toBe(AssetType::AI_IMAGE)
        ->provider->toBe(AssetProvider::REPLICATE)
        ->provider_id->toBe('gen-123')
        ->description->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE)
        ->and($asset->getFirstMedia())
        ->collection_name->toBe('default')
        ->model_id->toBe($asset->id)
        ->model_type->toBe($asset->getMorphClass());

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $initial->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 0,
        'active' => true,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $asset->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 0,
        'active' => false,
    ]);

    expect($faceless->assets)->toHaveCount(2)
        ->and($faceless->assets->first())
        ->id->toBe($initial->id)
        ->pivot->order->toBe(0)
        ->and($faceless->assets->last())
        ->id->toBe($asset->id)
        ->pivot->order->toBe(0);
});

it('charges credits for AI image generation after free limit', function () {
    Chat::fake();
    Storage::fake('spaces');
    Feature::define('video', true);

    Http::fake([
        '*' => Http::sequence()
            ->push(['id' => 'gen-123', 'status' => 'processing'])
            ->push(['id' => 'gen-123', 'status' => 'succeeded', 'output' => ['https://example.com/image.jpg']]),
    ]);

    $image = UploadedFile::fake()->image('image.jpg', 720, 1280)->size(1500);

    stream_wrapper_unregister('https');
    stream_wrapper_register('https', FakeStreamWrapper::class);

    FakeStreamWrapper::$content = file_get_contents($image->getRealPath());

    $url = 'https://example.com/image.jpg';

    $user = User::factory()->create();

    $initial = Asset::factory()->recycle($user)->withMedia()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
        'meta' => [
            'model' => 'fake-owner/fake-name',
            'input' => ['prompt' => '[PROMPT]'],
        ],
    ]);

    $faceless = Faceless::factory()->ai()->recycle($user)->create(['genre_id' => $genre->id]);

    $faceless->assets()->attach($initial->id, ['order' => 0]);

    Tracker::factory()->for($faceless, 'trackable')->create([
        'user_id' => $user->id,
        'name' => 'image-generation',
        'count' => 3,
        'limit' => 3,
    ]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/generate", [
        'index' => 0,
    ])->assertOk();

    expect($user->fresh()->remaining_credit_amount)->toBe(499);

    stream_wrapper_restore('https');
});

it('fails to generate AI image without sufficient credits', function () {
    Feature::define('video', true);

    $user = User::factory()->create(['remaining_credit_amount' => 0]);

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    Tracker::factory()->for($faceless, 'trackable')->create([
        'user_id' => $user->id,
        'name' => 'image-generation',
        'count' => 3,
        'limit' => 3,
    ]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/generate", [
        'index' => 0,
    ])->assertForbidden();

    expect(Asset::count())->toBe(0);
});

it('transloads stock media for a faceless video', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Storage::fake('spaces');

    $image = UploadedFile::fake()->image('stock.jpg', 720, 1280)->size(1500);

    Http::fake([
        'https://example.com/stock.jpg' => Http::response(null, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => $image->getSize(),
        ]),
    ]);

    stream_wrapper_unregister('https');
    stream_wrapper_register('https', FakeStreamWrapper::class);

    FakeStreamWrapper::$content = file_get_contents($image->getRealPath());

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
        'meta' => [
            'model' => 'fake-owner/fake-name',
            'input' => ['prompt' => '[PROMPT]'],
        ],
    ]);

    $faceless = Faceless::factory()->ai()->recycle($user)->create(['genre_id' => $genre->id]);

    $initial = Asset::factory()->recycle($user)->withMedia()->create();
    $faceless->assets()->attach($initial->id, ['order' => 0]);

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/transload", [
        'index' => 1,
        'type' => 'image/jpeg',
        'url' => 'https://example.com/stock.jpg',
    ])->assertOk();

    stream_wrapper_restore('https');

    $asset = Asset::query()->orderByDesc('id')->first();

    expect($asset)
        ->user_id->toBe($user->id)
        ->type->toBe(AssetType::STOCK_IMAGE)
        ->provider->toBe(AssetProvider::PEXELS)
        ->provider_id->toBeString()
        ->genre_id->toBe($faceless->genre_id)
        ->and($asset->getFirstMedia())
        ->collection_name->toBe('default')
        ->model_id->toBe($asset->id)
        ->model_type->toBe($asset->getMorphClass());

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $initial->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 0,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $asset->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
        'order' => 1,
    ]);

    expect($faceless->assets)->toHaveCount(2)
        ->and($faceless->assets->first())
        ->id->toBe($initial->id)
        ->pivot->order->toBe(0)
        ->and($faceless->assets->last())
        ->id->toBe($asset->id)
        ->pivot->order->toBe(1)
        ->and($response->json('data'))
        ->toHaveKeys([
            'id', 'user_id', 'provider_id', 'name', 'type', 'slug', 'description', 'status', 'is_private', 'order',
            'media', 'created_at', 'updated_at',
        ]);
});
