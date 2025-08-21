<?php

namespace Tests\Feature\Media;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('cannot fetch media metrics while unauthenticated', function () {
    $this->getJson('v1/media/metrics')->assertUnauthorized();
});

it('can fetch media metrics for authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Video::factory()->for($user)->count(5)->create();
    Video::factory()->for($otherUser)->count(3)->create(); // These should not be counted

    Asset::factory(7)->for($user)->create(['type' => AssetType::AI_IMAGE]);
    Asset::factory(3)->for($user)->create(['type' => AssetType::AI_VIDEO]);
    Asset::factory(2)->for($user)->create(['type' => AssetType::AUDIOS]); // These should not be counted
    Asset::factory(4)->for($otherUser)->create(['type' => AssetType::STOCK_IMAGE]); // These should not be counted

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/media/metrics');

    $response->assertOk();
    expect($response->json('data.videos_count'))->toBe(5)
        ->and($response->json('data.assets_count'))->toBe(10);
});

it('returns zero counts when user has no media', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/media/metrics');

    $response->assertOk();
    expect($response->json('data.videos_count'))->toBe(0)
        ->and($response->json('data.assets_count'))->toBe(0);
});

it('excludes audio assets from the count', function () {
    $user = User::factory()->create();

    Asset::factory(5)->for($user)->create(['type' => AssetType::AUDIOS]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/media/metrics');

    $response->assertOk();
    expect($response->json('data.assets_count'))->toBe(0);
});

it('correctly counts different asset types', function () {
    $user = User::factory()->create();

    Asset::factory(3)->for($user)->create(['type' => AssetType::AI_IMAGE]);
    Asset::factory(2)->for($user)->create(['type' => AssetType::STOCK_VIDEO]);
    Asset::factory(1)->for($user)->create(['type' => AssetType::THUMBNAIL]);
    Asset::factory(1)->for($user)->create(['type' => AssetType::WATERMARK]);
    Asset::factory(4)->for($user)->create(['type' => AssetType::AUDIOS]); // Should be excluded

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/media/metrics');

    $response->assertOk();

    expect($response->json('data.assets_count'))->toBe(7) // Total excluding audios
        ->and($response->json('data.videos_count'))->toBe(0); // No videos created
});