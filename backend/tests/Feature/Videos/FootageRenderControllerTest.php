<?php

namespace Tests\Feature\Videos;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Http;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Videos\Enums\VideoStatus;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Jobs\Renders\TriggerFootageRendering;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('triggers the render of a video footage and charge user credits', function () {
    Queue::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
    ]);

    $footage = Footage::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render", [
        'duration' => 30,
    ]);

    $response->assertAccepted();
    Queue::assertPushed(TriggerFootageRendering::class);

    expect($response->json('data'))
        ->video_id->toBe($footage->video_id)
        ->user_id->toBe($user->id)
        ->and($response->json('data.video'))
        ->status->toBe(VideoStatus::RENDERING->value)
        ->and($user->remaining_credit_amount)->toBe(497);
});

it('refund credits if video fails', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.creatomate.com/v1/renders' => Http::response([], 503),
    ]);

    $user = User::factory()->create();

    $footage = Footage::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum')->postJson("v1/videos/footage/{$footage->id}/render", [
        'duration' => 30,
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 500,
    ]);
});

it('fails to render a video of another user video footage', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $footage = Footage::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::GEN_FORBIDDEN->value,
    ]);
});

it('fails to render a video that is still syncing', function () {
    Queue::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->syncing()->create();
    $footage = Footage::factory()->for($video)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render", [
        'duration' => 30,
    ]);

    Queue::assertNotPushed(TriggerFootageRendering::class);

    $response->assertForbidden();
});

it('fails to render a video that is still rendering', function () {
    Queue::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->rendering()->create();
    $footage = Footage::factory()->for($video)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render", [
        'duration' => 30,
    ]);

    Queue::assertNotPushed(TriggerFootageRendering::class);

    $response->assertForbidden();
});

it('fails to render a video that has a failed real clone', function () {
    Queue::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $footage = Footage::factory()->recycle($user)->create();
    RealClone::factory()->for($footage)->failed()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render", [
        'duration' => 30,
    ]);

    Queue::assertNotPushed(TriggerFootageRendering::class);

    $response->assertForbidden();
});

it('fails to render a video with insufficient storage', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();
    $footage = Footage::factory()->recycle($user)->create();

    Media::factory()->for($footage->video, 'model')->create([
        'user_id' => $user->id,
        'size' => TestCase::BASE_MAX_ALLOWED_STORAGE + 200_000_000,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::REACH_PLAN_STORAGE_LIMIT->value,
    ]);
});

it('fails to render a video with insufficient credits', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->withoutCredits()->create();
    $footage = Footage::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("v1/videos/footage/{$footage->id}/render");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::INSUFFICIENT_CREDITS->value,
    ]);
});