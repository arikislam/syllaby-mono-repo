<?php

namespace Tests\Feature\Videos;

use Carbon\Carbon;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use Tests\Stubs\CreatomateStub;
use App\Syllaby\Metadata\Timeline;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('creates a draft untitled video footage', function () {
    Feature::define('video', true);

    $user = User::factory()->withDefaultFolder()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/videos/footage');

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe(($user->id))
        ->source->content->toMatchArray([
            'output_format' => 'mp4',
            'elements' => [],
        ]);

    $this->assertDatabaseHas('videos', [
        'idea_id' => null,
        'user_id' => $user->id,
        'title' => 'Untitled video',
        'status' => VideoStatus::DRAFT->value,
        'provider' => VideoProvider::CREATOMATE->value,
        'type' => Video::CUSTOM,
    ]);
});

it('creates a draft video with a title from an idea', function () {
    Feature::define('video', true);

    $user = User::factory()->withDefaultFolder()->create();
    $idea = Idea::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/videos/footage', [
        'title' => $idea->title,
        'idea_id' => $idea->id,
    ]);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe(($user->id))
        ->source->content->toMatchArray([
            'output_format' => 'mp4',
            'elements' => [],
        ]);

    $this->assertDatabaseHas('videos', [
        'idea_id' => $idea->id,
        'user_id' => $user->id,
        'title' => $idea->title,
        'status' => VideoStatus::DRAFT->value,
        'provider' => VideoProvider::CREATOMATE->value,
        'type' => Video::CUSTOM,
    ]);
});

it('creates a draft video and a event record for the calendar', function () {
    Feature::define('video', true);

    $format = config('common.iso_standard_format');
    Carbon::setTestNow(now());

    $user = User::factory()->withDefaultFolder()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/videos/footage', [
        'title' => 'My awesome idea',
        'starts_at' => now()->format($format),
        'ends_at' => now()->format($format),
    ]);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe(($user->id))
        ->source->content->toMatchArray([
            'output_format' => 'mp4',
            'elements' => [],
        ]);

    $this->assertDatabaseHas('videos', [
        'idea_id' => null,
        'user_id' => $user->id,
        'title' => 'My awesome idea',
        'status' => VideoStatus::DRAFT->value,
        'provider' => VideoProvider::CREATOMATE->value,
        'type' => Video::CUSTOM,
    ]);

    $this->assertDatabaseHas('footages', [
        'user_id' => $user->id,
        'video_id' => $response->json('data.video_id'),
    ]);

    $this->assertDatabaseHas('timelines', [
        'user_id' => $user->id,
        'model_id' => $response->json('data.id'),
        'model_type' => Relation::getMorphAlias(Footage::class),
    ]);

    $this->assertDatabaseHas('events', [
        'model_id' => $response->json('data.video_id'),
        'model_type' => 'video',
        'starts_at' => now()->toDateTimeString(),
        'ends_at' => now()->toDateTimeString(),
    ]);
});

it('can update the footage width and height', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $footage = Footage::factory()->recycle($user)->create();
    Timeline::factory()->for($footage, 'model')->create(['content' => CreatomateStub::timeline()]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/videos/footage/{$footage->id}", [
        'width' => 720,
        'height' => 720,
    ]);

    $response->assertOk();
    expect($response->json('data'))->source->content->toMatchArray([
        'width' => 720,
        'height' => 720,
    ]);
});

it('ensure video status remains unchanged when no changes are made to footage', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->completed()->create();

    $timeline = CreatomateStub::timeline();

    $footage = Footage::factory()->for($video)->recycle($user)->create();

    $timeline = Timeline::factory()->for($footage, 'model')->create([
        'content' => $timeline,
    ]);

    $this->actingAs($user, 'sanctum');
    $this->patchJson("/v1/videos/footage/{$footage->id}", [
        'source' => json_encode($timeline->content),
    ]);

    expect($video->fresh()->status)->toBe(VideoStatus::COMPLETED);
});

it('changes video status status to draft when changes are made to footage', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->completed()->create();

    $timeline = CreatomateStub::timeline();
    $footage = Footage::factory()->for($video)->recycle($user)->create();
    Timeline::factory()->for($footage, 'model')->create(['content' => $timeline]);

    $this->actingAs($user, 'sanctum');
    $this->patchJson("/v1/videos/footage/{$footage->id}", [
        'width' => 720,
        'height' => 720,
    ]);

    expect($video->fresh()->status)->toBe(VideoStatus::DRAFT);
});

it('fails to update a video while its either rendering or syncing', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->rendering()->create();
    $footage = Footage::factory()->for($video)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/videos/footage/{$footage->id}");

    $response->assertForbidden();
});

it('fails to update another user video footage', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $footage = Footage::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/videos/footage/{$footage->id}");

    $response->assertForbidden();
});
