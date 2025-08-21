<?php

namespace Tests\Feature\Previews;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Videos\Enums\VideoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Jobs\Faceless\TriggerMediaGeneration;
use App\Syllaby\Videos\Jobs\Faceless\GenerateFacelessVoiceOver;

uses(RefreshDatabase::class);

it('can show a video preview for authorized user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $video = Video::factory()->for($user)->create();

    $response = $this->getJson("/v1/previews/{$video->id}");

    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($video->id)
        ->title->toBe($video->title)
        ->status->toBe($video->status->value);
});

it('cannot show video preview for unauthorized user', function () {
    $user = User::factory()->create();
    $video = Video::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/previews/{$video->id}");

    $response->assertForbidden();
});

it('can create and render a new video preview', function () {
    Bus::fake();

    $user = User::factory()->withDefaultFolder()->create();
    $voice = Voice::factory()->create();
    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/previews/render', [
        'voice_id' => $voice->id,
        'script' => 'This is a test script',
        'genre_id' => $genre->id,
        'duration' => 40,
        'aspect_ratio' => '16:9',
        'captions' => [
            'font_family' => 'lively',
            'position' => 'bottom',
        ],
    ]);

    $response->assertStatus(202);

    $response->assertJsonFragment([
        'title' => 'My First Syllaby Video',
        'status' => VideoStatus::RENDERING->value,
    ]);

    Bus::assertChained([
        GenerateFacelessVoiceOver::class,
        TriggerMediaGeneration::class,
    ]);
});

it('validates required fields when creating preview', function () {
    $user = User::factory()->withDefaultFolder()->create();
    $voice = Voice::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/previews/render', [
        'voice_id' => $voice->id,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors([
        'genre_id', 'script', 'duration',
    ]);
});
