<?php

namespace Tests\Feature\Tags;

use App\Syllaby\Tags\Tag;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Templates\Template;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can display a list of all tags', function () {
    $user = User::factory()->create();

    $tags = Tag::factory()->count(4)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/tags');

    $response->assertOk();

    $expected = $tags->pluck('id')->toArray();
    $actual = array_column($response->json('data'), 'id');

    expect($expected)->toBe($actual);
});

it('can display a list of only tags that are associated with templates of type video', function () {
    $user = User::factory()->create();

    Tag::factory()->count(2)->create();

    Tag::factory()->count(3)->hasAttached(
        Template::factory()->count(2)->state(fn () => ['type' => 'video'])
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/tags?filter[templates]=video');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('can display a list of only tags that are associated with audio media files', function () {
    $user = User::factory()->create();

    Tag::factory()->count(2)->create();

    $asset = Asset::factory()->global()->create([
        'type' => AssetType::AUDIOS->value,
    ]);

    Tag::factory()->count(3)->hasAttached(
        Media::factory()->count(2)->for($asset, 'model')->state(['collection_name' => AssetType::AUDIOS->value])
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/tags?filter[media]=audios');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('can display a list of only authenticated user tags', function () {
    $user = User::factory()->create();
    $john = User::factory()->create();

    Tag::factory()->count(5)->sequence(
        ['user_id' => null],
        ['user_id' => null],
        ['user_id' => $user->id],
        ['user_id' => $john->id],
        ['user_id' => $john->id]
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/tags');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('can display a single tag', function () {
    $user = User::factory()->create();

    $tag = Tag::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/tags/{$tag->id}");

    $response->assertOk();
    expect($response->json('data'))->id->toBe($tag->id);
});

it('can display a single tag that has templates associated', function () {
    $user = User::factory()->create();

    $tag = Tag::factory()->hasAttached(
        Template::factory()->count(2)->state(fn () => ['type' => 'video'])
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/tags/{$tag->id}?filter[templates]=video");

    $response->assertOk();
    expect($response->json('data'))->id->toBe($tag->id);
});
