<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Assets\Enums\AssetProvider;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('requires authentication', function () {
    $faceless = Faceless::factory()->create();

    $this->getJson("/v1/videos/faceless/{$faceless->id}/assets")->assertUnauthorized();
});

it('returns all active assets for a faceless video', function () {
    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create(['genre_id' => $genre->id]);

    [$asset1, $asset2] = Asset::factory(3)->recycle($user)->create([
        'type' => AssetType::AI_IMAGE,
        'provider' => AssetProvider::REPLICATE,
    ]);

    $faceless->assets()->attach([
        $asset1->id => ['order' => 0, 'active' => true],
        $asset2->id => ['order' => 1, 'active' => true],
    ]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $asset1->id)
        ->assertJsonPath('data.1.id', $asset2->id);
});

it('returns assets filtered by index', function () {
    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create(['genre_id' => $genre->id]);

    [$asset1, $asset2] = Asset::factory(3)->recycle($user)->create([
        'type' => AssetType::AI_IMAGE,
        'provider' => AssetProvider::REPLICATE,
    ]);

    $faceless->assets()->attach([
        $asset1->id => ['order' => 0],
        $asset2->id => ['order' => 1],
    ]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets?index=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $asset2->id);
});

it('authorizes access to faceless video', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets")
        ->assertForbidden();
});

it('includes media relationship in response', function () {
    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->withMedia()->create();
    $faceless->assets()->attach($asset->id, ['order' => 0, 'active' => true]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.0.media')
            ->has('data.0.media.0.id')
            ->etc()
        );
});

it('shows a specific asset for a faceless video', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->withMedia()->create([
        'type' => AssetType::AI_IMAGE,
        'provider' => AssetProvider::REPLICATE,
    ]);

    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets/{$asset->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn ($json) => $json
                ->where('id', $asset->id)
                ->where('type', AssetType::AI_IMAGE->value)
                ->has('media')
                ->etc()
            )->etc()
        );
});

it('prevents unauthorized users from viewing assets', function () {
    $owner = User::factory()->create();

    $asset = Asset::factory()->recycle($owner)->withMedia()->create();

    $faceless = Faceless::factory()->recycle($owner)->create();
    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs(User::factory()->create())
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets/{$asset->id}")
        ->assertForbidden();
});

it('prevents viewing assets from other users faceless videos', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->create();

    $asset = Asset::factory()->recycle($user)->withMedia()->create();
    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets/{$asset->id}")
        ->assertForbidden();
});

it('requires authentication for viewing specific asset', function () {
    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();
    $asset = Asset::factory()->recycle($user)->create();

    $this->getJson("/v1/videos/faceless/{$faceless->id}/assets/{$asset->id}")
        ->assertUnauthorized();
});

it('includes media relationship in single asset response', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->recycle($user)->withMedia()->create();

    $faceless = Faceless::factory()->recycle($user)->create();
    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets/{$asset->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.media', fn ($json) => $json
                ->has('0.id')
                ->has('0.file_name')
                ->has('0.mime_type')
                ->has('0.size')
                ->etc()
            )->etc()
        );
});

it('returns 404 for non-existent asset', function () {
    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/assets/99999")
        ->assertNotFound();
});

it('can attach an asset to a faceless video at specific index', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
            'id' => $asset->id,
            'index' => 0,
        ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn ($json) => $json
                ->where('id', $asset->id)
                ->where('order', 0)
                ->etc()
            )->etc()
        );

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('can attach multiple assets at the same index', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    [$asset1, $asset2] = Asset::factory(2)->recycle($user)->create();

    $faceless->assets()->attach($asset1->id, ['order' => 0]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset2->id,
        'index' => 0,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn ($json) => $json
                ->where('id', $asset2->id)
                ->where('order', 0)
                ->etc()
            )->etc()
        );

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset1->id,
        'order' => 0,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset2->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('prevents duplicate asset attachments at the same index', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->create();

    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset->id,
        'index' => 0,
    ])->assertOk();

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('prevents unauthorized users from attaching assets', function () {
    $owner = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($owner)->create();

    $asset = Asset::factory()->recycle($owner)->create();

    $this->actingAs(User::factory()->create())->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset->id,
        'index' => 0,
    ])->assertForbidden();
});

it('validates asset ownership', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => Asset::factory()->create()->id,
        'index' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors('id');
});

it('sets active flag to true for already attached assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->create();

    $faceless->assets()->attach($asset->id, ['order' => 0, 'active' => false]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset->id,
        'index' => 0,
    ])->assertOk();

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('deactivates existing assets at the same index when attaching a new asset', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    [$asset1, $asset2] = Asset::factory(2)->recycle($user)->create();

    $faceless->assets()->attach($asset1->id, [
        'order' => 0,
        'active' => true,
    ]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset2->id,
        'index' => 0,
    ])->assertOk();

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset1->id,
        'order' => 0,
        'active' => false,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset2->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('only deactivates assets at the specified index', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    [$asset1, $asset2, $asset3] = Asset::factory(3)->recycle($user)->create();

    $faceless->assets()->attach([
        $asset1->id => ['order' => 0, 'active' => true],
        $asset2->id => ['order' => 1, 'active' => true],
    ]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset3->id,
        'index' => 0,
    ])->assertOk();

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset1->id,
        'order' => 0,
        'active' => false,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset2->id,
        'order' => 1,
        'active' => true,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset3->id,
        'order' => 0,
        'active' => true,
    ]);
});

it('handles reactivation of previously deactivated asset', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->ai()->recycle($user)->create();

    [$asset1, $asset2] = Asset::factory(2)->recycle($user)->create();

    $faceless->assets()->attach([
        $asset1->id => ['order' => 0, 'active' => true],
        $asset2->id => ['order' => 0, 'active' => false],
    ]);

    $this->actingAs($user)->patchJson("/v1/videos/faceless/{$faceless->id}/assets", [
        'id' => $asset2->id,
        'index' => 0,
    ])
        ->assertOk();

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset1->id,
        'order' => 0,
        'active' => false,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_type' => $faceless->getMorphClass(),
        'model_id' => $faceless->id,
        'asset_id' => $asset2->id,
        'order' => 0,
        'active' => true,
    ]);
});
