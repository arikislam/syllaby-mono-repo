<?php

namespace Tests\Feature\Videos;

use Mockery;
use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Faceless;
use App\Http\Responses\ErrorCode;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\FacelessType;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Credits\Jobs\ProcessFacelessVideoCharge;
use App\Syllaby\Videos\Jobs\Faceless\ManageFacelessAssets;
use App\Syllaby\Videos\Jobs\Faceless\FindStockFootageClips;
use App\Syllaby\Videos\Jobs\Faceless\TriggerMediaGeneration;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;
use App\Syllaby\Videos\Jobs\Faceless\GenerateFacelessVoiceOver;
use App\Syllaby\Videos\Jobs\Faceless\ExtractStockFootageKeywords;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('triggers the render of a faceless single clip video', function () {
    Bus::fake();

    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->singleClip()->withSource()
        ->for(Video::factory()->draft()->for($user))
        ->for($user)->create();

    $voice = Voice::factory()->create();

    $response = $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'script' => 'Foo Bar',
        'voice_id' => $voice->id,
        'background_id' => $faceless->background_id,
        'aspect_ratio' => '9:16',
        'duration' => $faceless->estimated_duration,
    ])->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(FacelessType::SINGLE_CLIP->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull()
        ->and($response->json('data.genre'))->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('triggers the render of a faceless ai visuals video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->withSource()->create([
        'background_id' => null,
        'genre_id' => Genre::factory()->consistent()->active()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $response = $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'script' => 'Foo Bar',
        'transition' => 'fade',
        'genre' => $faceless->genre?->slug,
        'voice_id' => $faceless->voice_id,
        'duration' => $faceless->estimated_duration,
        'aspect_ratio' => '9:16',
    ])->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(Faceless::AI_VISUALS)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull()
        ->and($response->json('data'))
        ->genre->toBeArray()
        ->genre->slug->toBe(StoryGenre::COMIC_BOOK->value)
        ->genre->name->toBe(str(StoryGenre::COMIC_BOOK->value)->headline()->toString());

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        TriggerMediaGeneration::class,
    ]);
});

it('triggers the render of a faceless b-roll video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->bRoll()->recycle($user)->withSource()->create([
        'background_id' => null,
        'genre_id' => null,
    ]);

    $response = $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'genre_id' => null,
        'script' => 'Foo Bar',
        'transition' => 'none',
        'background_id' => null,
        'aspect_ratio' => '9:16',
        'voice_id' => $faceless->voice_id,
        'duration' => $faceless->estimated_duration,
    ])->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->background_id->toBeNull()
        ->type->toBe(FacelessType::B_ROLL->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull()
        ->and($response->json('data.genre'))->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        ExtractStockFootageKeywords::class,
        FindStockFootageClips::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('fails to render a video if user does not own it', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $faceless = Faceless::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson("v1/videos/faceless/{$faceless->id}/render")
        ->assertForbidden();
});

it('allows to add a background music to the faceless videos', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $music = Media::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->withSource()->create([
        'background_id' => null,
        'genre_id' => Genre::factory()->consistent()->active()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $response = $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'volume' => 'low',
        'script' => 'Foo Bar',
        'transition' => 'fade',
        'aspect_ratio' => '9:16',
        'genre' => $faceless->genre?->slug,
        'music_id' => $music->id,
        'duration' => 60,
        'voice_id' => $faceless->voice_id,
    ])->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->music_id->toBe($music->id)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull()
        ->and($response->json('data'))
        ->genre->toBeArray()
        ->genre->slug->toBe(StoryGenre::COMIC_BOOK->value)
        ->genre->name->toBe(str(StoryGenre::COMIC_BOOK->value)->headline()->toString());

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        TriggerMediaGeneration::class,
    ]);
});

it('allows to change the font color', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $music = Media::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->withSource()->create([
        'background_id' => null,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $response = $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'volume' => 'low',
        'script' => 'Foo Bar',
        'transition' => 'fade',
        'aspect_ratio' => '9:16',
        'genre' => $faceless->genre?->slug,
        'music_id' => $music->id,
        'duration' => 60,
        'voice_id' => $faceless->voice_id,
    ])->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->music_id->toBe($music->id)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull()
        ->and($response->json('data'))
        ->genre->toBeArray()
        ->genre->slug->toBe(StoryGenre::COMIC_BOOK->value)
        ->genre->name->toBe(str(StoryGenre::COMIC_BOOK->value)->headline()->toString());

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        TriggerMediaGeneration::class,
    ]);
});

it('handles correctly if initial source is null', function () {
    Bus::fake();

    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->singleClip()
        ->for(Video::factory()->draft()->for($user))
        ->for($user)->create(['script' => null]);

    $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'voice_id' => $faceless->voice_id,
        'background_id' => $faceless->background_id,
        'aspect_ratio' => '9:16',
        'script' => 'This is a updated script',
        'duration' => $faceless->estimated_duration,
    ])->assertAccepted();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('fails to render a video with in-sufficient credits', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->withoutCredits()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/render")
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::INSUFFICIENT_CREDITS->value]);
});

it('fails to re-render a video if it is syncing', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()
        ->for(Video::factory()->syncing()->for($user)->create())
        ->recycle($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/render")
        ->assertForbidden();
});

it('fails to re-render a video if it is rendering', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()
        ->for(Video::factory()->rendering()->for($user)->create())
        ->recycle($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/render")
        ->assertForbidden();
});

it('fails to render a video with in-sufficient storage', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/render")
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::REACH_PLAN_STORAGE_LIMIT->value]);
});

it('moves the video to the specified destination folder on render', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $origin = Folder::factory()->for($user)->create(['name' => 'Origin Folder']);
    $destination = Folder::factory()->for($user)->create(['name' => 'Destination Folder']);

    $video = Video::factory()->draft()->for($user)->create();
    $video->resource()->create(['user_id' => $user->id, 'parent_id' => $origin->resource->id]);

    $voice = Voice::factory()->create();
    $faceless = Faceless::factory()->for($video)->for($user)->create(['genre_id' => null]);

    $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'script' => 'Foo Bar',
        'voice_id' => $voice->id,
        'background_id' => $faceless->background_id,
        'aspect_ratio' => '9:16',
        'duration' => $faceless->estimated_duration,
        'destination_id' => $destination->resource->id,
    ])->assertAccepted();

    $video->refresh();
    expect($video->resource->parent_id)->toBe($destination->resource->id);
});

it('triggers render of a url-based faceless video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()
        ->for(Video::factory()->draft()->for($user))
        ->for($user)
        ->create([
            'type' => Faceless::URL_BASED,
            'background_id' => null,
        ]);

    [$asset1, $asset2] = Asset::factory(2)->recycle($user)->create();
    $faceless->assets()->attach($asset1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($asset2, ['order' => 1, 'active' => true]);

    $this->actingAs($user)->postJson("v1/videos/faceless/{$faceless->id}/render", [
        'script' => 'Foo Bar',
        'voice_id' => $faceless->voice_id,
        'aspect_ratio' => '9:16',
        'duration' => $faceless->estimated_duration,
    ])->assertAccepted();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        ManageFacelessAssets::class,
        BuildFacelessVideoSource::class,
    ]);
});

afterEach(function () {
    Mockery::close();
});
