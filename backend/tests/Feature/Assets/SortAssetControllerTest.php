<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Assets\VideoAsset;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can move assets with same order to the first position', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(4)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 2]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'after_id' => null,
        'asset_id' => $assets->get(2)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(2);
});

it('can move a single order asset to the first position', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(4)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 3]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'after_id' => null,
        'asset_id' => $assets->get(2)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(2);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(3);
});

it('can move assets with same order to a position between other assets', function () {
    $user = User::factory()->create();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(5)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 2]);
    $faceless->assets()->attach($assets->get(4), ['order' => 3]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'asset_id' => $assets->get(2)->id,
        'after_id' => $assets->get(0)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(2);
    expect(VideoAsset::where('asset_id', $assets->get(4)->id)->first()->order)->toBe(3);
});

it('can move a single order asset to a position between other assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(4)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 3]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'after_id' => $assets->get(0)->id,
        'asset_id' => $assets->get(3)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(2);
    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(3);
});

it('can move assets with same order to the last position', function () {
    $user = User::factory()->create();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(5)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 2]);
    $faceless->assets()->attach($assets->get(4), ['order' => 3]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'asset_id' => $assets->get(2)->id,
        'after_id' => $assets->get(4)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(4)->id)->first()->order)->toBe(2);
    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(3);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(3);
});

it('can move a single order asset to the last position', function () {
    $user = User::factory()->create();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $assets = Asset::factory()->recycle($user)->count(5)->create();

    $faceless->assets()->attach($assets->get(0), ['order' => 0]);
    $faceless->assets()->attach($assets->get(1), ['order' => 1]);
    $faceless->assets()->attach($assets->get(2), ['order' => 2]);
    $faceless->assets()->attach($assets->get(3), ['order' => 3]);
    $faceless->assets()->attach($assets->get(4), ['order' => 4]);

    $this->actingAs($user);
    $response = $this->putJson('/v1/assets/sort', [
        'asset_id' => $assets->get(1)->id,
        'after_id' => $assets->get(4)->id,
        'model_id' => $faceless->id,
        'model_type' => $faceless->getMorphClass(),
    ]);

    $response->assertOk();

    expect(VideoAsset::where('asset_id', $assets->get(0)->id)->first()->order)->toBe(0);
    expect(VideoAsset::where('asset_id', $assets->get(2)->id)->first()->order)->toBe(1);
    expect(VideoAsset::where('asset_id', $assets->get(3)->id)->first()->order)->toBe(2);
    expect(VideoAsset::where('asset_id', $assets->get(4)->id)->first()->order)->toBe(3);
    expect(VideoAsset::where('asset_id', $assets->get(1)->id)->first()->order)->toBe(4);
});
