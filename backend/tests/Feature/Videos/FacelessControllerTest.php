<?php

namespace Tests\Feature\Videos;

use Carbon\Carbon;
use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Trackers\Tracker;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display a given faceless video by id', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user);
    $response = $this->getJson("/v1/videos/faceless/{$faceless->id}");

    $response->assertOk();
    expect($response->json('data'))->id->toBe($faceless->id);
});

it('display a given faceless video by id with the associated tracker', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create([
        'genre_id' => $genre->id,
    ]);

    Tracker::factory()->for($faceless, 'trackable')->create([
        'user_id' => $user->id,
        'name' => 'image-generation',
    ]);

    $this->actingAs($user);
    $response = $this->getJson("/v1/videos/faceless/{$faceless->id}?include=trackers");

    $response->assertOk();
    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->trackers->toBeArray();
});

it('fails to display another user faceless video', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $faceless = Faceless::factory()->create();

    $this->actingAs($user);
    $response = $this->getJson("/v1/videos/faceless/{$faceless->id}");

    $response->assertForbidden();
});

it('can create an untitled faceless video', function () {
    Feature::define('video', true);

    $user = User::factory()->withDefaultFolder()->create();

    $response = $this->actingAs($user)->postJson('/v1/videos/faceless')
        ->assertCreated();

    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->video->title->toBe('Untitled video')
        ->video->status->toBe('draft')
        ->video->provider->toBe('creatomate')
        ->source->toBeNull();

    $this->assertDatabaseHas(Faceless::class, [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas(Video::class, [
        'id' => $response->json('data.video.id'),
        'user_id' => $user->id,
        'title' => 'Untitled video',
        'status' => 'draft',
        'provider' => 'creatomate',
        'type' => Video::FACELESS,
    ]);
});

it('can create a faceless video from idea title', function () {
    Feature::define('video', true);

    $user = User::factory()->withDefaultFolder()->create();
    $idea = Idea::factory()->create();

    $response = $this->actingAs($user)->postJson('/v1/videos/faceless', [
        'title' => $idea->title,
        'idea_id' => $idea->id,
    ])->assertCreated();

    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->video->title->toBe($idea->title)
        ->video->status->toBe('draft')
        ->video->provider->toBe('creatomate')
        ->video->idea_id->toBe($idea->id);

    $this->assertDatabaseHas(Faceless::class, [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas(Video::class, [
        'id' => $response->json('data.video.id'),
        'user_id' => $user->id,
        'idea_id' => $idea->id,
        'title' => $idea->title,
        'status' => 'draft',
        'provider' => 'creatomate',
        'type' => Video::FACELESS,
    ]);
});

it('can create a faceless video with a calendar event', function () {
    Carbon::setTestNow('2021-01-01 00:00:00');
    Feature::define('video', true);

    $user = User::factory()->withDefaultFolder()->create();

    $response = $this->actingAs($user)->postJson('/v1/videos/faceless', [
        'starts_at' => now()->format(config('common.iso_standard_format')),
        'ends_at' => now()->format(config('common.iso_standard_format')),
    ])->assertCreated();

    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->video->title->toBe('Untitled video')
        ->video->status->toBe('draft')
        ->video->provider->toBe('creatomate');

    $this->assertDatabaseHas(Faceless::class, [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas(Video::class, [
        'id' => $response->json('data.video.id'),
        'user_id' => $user->id,
        'title' => 'Untitled video',
        'status' => 'draft',
        'provider' => 'creatomate',
        'type' => Video::FACELESS,
    ]);

    $this->assertDatabaseHas('events', [
        'model_id' => $response->json('data.video.id'),
        'model_type' => 'video',
        'user_id' => $user->id,
        'starts_at' => now()->format(config('common.iso_standard_format')),
        'ends_at' => now()->format(config('common.iso_standard_format')),
        'completed_at' => null,
    ]);
});

it('doesnt update hashes and status if everything is same', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()
        ->for(Video::factory()->completed()->for($user)->create())
        ->recycle($user)
        ->create();

    $hashes = $faceless->hash;

    $response = $this->actingAs($user)->patchJson("v1/videos/faceless/{$faceless->id}", [
        'script' => $faceless->script,
        'background_id' => $faceless->background_id,
        'voice_id' => $faceless->voice_id,
        'captions' => [
            'font_family' => $faceless->options->font_family,
        ],
    ])->assertOk();

    expect($response->json('data'))
        ->script->toBe($faceless->script)
        ->voice_id->toBe($faceless->voice_id)
        ->options->font_family->toBe($faceless->options->font_family)
        ->hash->options->toBe($hashes['options'])
        ->hash->speech->toBe($hashes['speech']);
});

it('doesnt allow update if video is rendering', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()
        ->for(Video::factory()->rendering()->for($user)->create())
        ->for($user)
        ->create();

    $this->actingAs($user)->patchJson("v1/videos/faceless/{$faceless->id}", [
        'script' => 'This is a new script',
    ])->assertForbidden();
});

it('doesnt allow update if video is syncing', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()
        ->for(Video::factory()->syncing()->for($user)->create())
        ->for($user)
        ->create();

    $this->actingAs($user)->patchJson("v1/videos/faceless/{$faceless->id}", [
        'script' => 'This is a new script',
    ])->assertForbidden();
});

it('doesnt allow updating a video if user doesnt own', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()->for($user)->create();

    $this->actingAs(User::factory()->create())->patchJson("v1/videos/faceless/{$faceless->id}", [
        'script' => 'This is a new script',
        'voice_id' => $faceless->voice_id,
    ])->assertForbidden();
});

it('doesnt allow updating a video if feature is disabled', function () {
    Feature::define('video', false);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->withSource()->for($user)->create();

    $this->actingAs($user)->patchJson("v1/videos/faceless/{$faceless->id}", [
        'script' => 'This is a new script',
        'voice_id' => $faceless->voice_id,
    ])->assertForbidden();
});
