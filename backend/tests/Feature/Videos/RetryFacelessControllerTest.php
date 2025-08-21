<?php

namespace Tests\Feature\Videos;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Faceless;
use App\Http\Responses\ErrorCode;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\FacelessType;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
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

it('allows retrying a failed faceless single clip video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $voice = Voice::factory()->create();

    $video = Video::factory()->failed()->for($user)->create();
    $faceless = Faceless::factory()->singleClip()
        ->for($video)
        ->for($user)
        ->create([
            'voice_id' => $voice->id,
            'script' => 'This is a test script',
        ]);

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(FacelessType::SINGLE_CLIP->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('allows retrying a failed faceless ai visuals video', function () {
    Bus::fake();

    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $video = Video::factory()->failed()->for($user)->create();
    $faceless = Faceless::factory()->ai()
        ->for($video)
        ->for($user)
        ->create();

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(FacelessType::AI_VISUALS->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        TriggerMediaGeneration::class,
    ]);
});

it('allows retrying a failed faceless b-roll video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $video = Video::factory()->failed()->for($user)->create();
    $faceless = Faceless::factory()->broll()
        ->for($video)
        ->for($user)
        ->create();

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(FacelessType::B_ROLL->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        ExtractStockFootageKeywords::class,
        FindStockFootageClips::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('allows retrying a failed faceless url-based video', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $video = Video::factory()->failed()->for($user)->create();

    $faceless = Faceless::factory()->urlBased()
        ->for($video)
        ->for($user)
        ->create();

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(FacelessType::URL_BASED->value)
        ->video->status->toBe(VideoStatus::RENDERING->value)
        ->video->synced_at->toBeNull();

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        ProcessFacelessVideoCharge::class,
        ManageFacelessAssets::class,
        BuildFacelessVideoSource::class,
    ]);
});

it('fails to retry a video if user does not own it', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $faceless = Faceless::factory()
        ->for(Video::factory()->failed())
        ->create();

    $this->actingAs(User::factory()->create())
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertForbidden();
});

it('fails to retry a video if it is not in failed state', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $rendering = Faceless::factory()
        ->for(Video::factory()->rendering()->for($user)->create())
        ->for($user)
        ->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$rendering->id}/retry")
        ->assertForbidden();
});

it('fails to retry a video with insufficient credits', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->withoutCredits()->create();

    $video = Video::factory()->failed()->for($user)->create();
    $faceless = Faceless::factory()->for($video)->for($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::INSUFFICIENT_CREDITS->value]);
});

it('fails to retry a video with insufficient storage', function () {
    $user = User::factory()->create();

    $video = Video::factory()->failed()->for($user)->create();
    $faceless = Faceless::factory()->for($video)->for($user)->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::REACH_PLAN_STORAGE_LIMIT->value]);
});

it('only charges for exported videos if credits were refunded', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create(['remaining_credit_amount' => 100]);
    $voice = Voice::factory()->create();

    $video = Video::factory()->failed()->for($user)->create(['exports' => 1]);

    $faceless = Faceless::factory()->singleClip()
        ->for($video)
        ->for($user)
        ->create([
            'voice_id' => $voice->id,
            'script' => 'This is a test script',
        ]);

    CreditHistory::factory()->create([
        'user_id' => $user->id,
        'creditable_id' => $faceless->id,
        'creditable_type' => $faceless->getMorphClass(),
        'description' => CreditEventEnum::REFUNDED_CREDITS->value,
    ]);

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($user->fresh()->remaining_credit_amount)->toBeLessThan(100);

    Bus::assertDispatched(BuildFacelessVideoSource::class);
    Bus::assertNotDispatched(GenerateFacelessVoiceOver::class);
});

it('does not charge for exported videos if credits were not refunded', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create(['remaining_credit_amount' => 100]);
    $voice = Voice::factory()->create();

    $video = Video::factory()->failed()->for($user)->create(['exports' => 1]);

    $faceless = Faceless::factory()->singleClip()
        ->for($video)
        ->for($user)
        ->create([
            'voice_id' => $voice->id,
            'script' => 'This is a test script',
        ]);

    $initial = $user->remaining_credit_amount;

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$faceless->id}/retry")
        ->assertAccepted();

    expect($user->fresh()->remaining_credit_amount)->toBe($initial);

    Bus::assertDispatched(BuildFacelessVideoSource::class);
    Bus::assertNotDispatched(GenerateFacelessVoiceOver::class);
});
