<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\Queue;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Assets\Jobs\DeleteBulkAssetsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('displays the assets of the user', function () {
    $user = User::factory()->create();

    Asset::factory(4)->recycle($user)->aiImage()->create();

    $this->actingAs($user)->getJson('/v1/assets')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('displays bookmark status in asset list', function () {
    $user = User::factory()->create();

    $bookmarkedAsset = Asset::factory()->for($user)->aiImage()->create();
    $normalAsset = Asset::factory()->for($user)->aiImage()->create();

    // Add bookmark to first asset
    $bookmarkedAsset->bookmarks()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/v1/assets')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // Find the bookmarked asset in response
    $assets = $response->json('data');
    $bookmarkedItem = collect($assets)->firstWhere('id', $bookmarkedAsset->id);
    $normalItem = collect($assets)->firstWhere('id', $normalAsset->id);

    expect($bookmarkedItem['is_bookmarked'])->toBeTrue();
    expect($normalItem['is_bookmarked'])->toBeFalse();
});

it('displays the assets of only the user', function () {
    $user = User::factory()->create();

    Asset::factory(4)->create();

    $this->actingAs($user)->getJson('/v1/assets')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('can filter assets by type', function () {
    $user = User::factory()->create();

    $video = Asset::factory()->aiVideo()->for($user)->create();
    Asset::factory()->aiImage()->for($user)->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[type]='.AssetType::AI_VIDEO->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $video->id)
        ->assertJsonPath('data.0.type', AssetType::AI_VIDEO->value);
});

it('can filter assets by status', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->success()->create();
    Asset::factory()->aiVideo()->for($user)->failed()->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[status]='.AssetStatus::SUCCESS->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $asset->id)
        ->assertJsonPath('data.0.status', $asset->status->value);
});

it('can filter assets by type and status', function () {
    $user = User::factory()->create();

    [$success, $failed] = Asset::factory()->aiVideo()->for($user)->forEachSequence(
        ['status' => AssetStatus::SUCCESS],
        ['status' => AssetStatus::FAILED]
    )->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[type]='.AssetType::AI_VIDEO->value.'&filter[status]='.AssetStatus::SUCCESS->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $success->id)
        ->assertJsonPath('data.0.type', AssetType::AI_VIDEO->value)
        ->assertJsonPath('data.0.status', AssetStatus::SUCCESS->value);
});

it('can filter assets by orientation', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->portrait()->create();
    Asset::factory()->aiVideo()->for($user)->landscape()->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[orientation]=portrait')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $asset->id)
        ->assertJsonPath('data.0.orientation', 'portrait');
});

it('can filter assets by genre', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    [$asset1, $asset2] = Asset::factory()->aiVideo()->for($user)->forEachSequence(
        ['genre_id' => $genre->id],
        ['genre_id' => null]
    )->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[genre]='.$genre->slug)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $asset1->id)
        ->assertJsonPath('data.0.genre_id', $genre->id);
});

it('can filter assets by genre and type', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $assetVideo = Asset::factory()->aiVideo()->for($user)->create(['genre_id' => $genre->id]);
    Asset::factory()->aiImage()->for($user)->create(['genre_id' => $genre->id]);
    Asset::factory()->aiVideo()->for($user)->create(['genre_id' => null]);

    $this->actingAs($user)->getJson('/v1/assets?filter[genre]='.$genre->slug.'&filter[type]='.AssetType::AI_VIDEO->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $assetVideo->id)
        ->assertJsonPath('data.0.genre_id', $genre->id)
        ->assertJsonPath('data.0.type', AssetType::AI_VIDEO->value);
});

it('can filter assets by multiple genre slugs', function () {
    $user = User::factory()->create();

    [$genre1, $genre2, $genre3] = Genre::factory(3)->create();

    [$asset1, $asset2, $asset3] = Asset::factory()->aiVideo()->for($user)->forEachSequence(
        ['genre_id' => $genre1->id],
        ['genre_id' => $genre2->id],
        ['genre_id' => $genre3->id]
    )->create();

    $this->actingAs($user)->getJson('/v1/assets?filter[genre]='.$genre1->slug.','.$genre2->slug)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $asset2->id)
        ->assertJsonPath('data.1.id', $asset1->id);
});

it('can display which assets are used in other videos', function () {
    $user = User::factory()->create();

    [$used, $unused] = Asset::factory(2)->aiVideo()->for($user)->create();

    $faceless = Faceless::factory()->for($user)->create();
    $faceless->assets()->attach($used, ['order' => 0]);

    $this->actingAs($user)->getJson('/v1/assets')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_used', false)
        ->assertJsonPath('data.0.id', $unused->id)
        ->assertJsonPath('data.1.is_used', true)
        ->assertJsonPath('data.1.id', $used->id);
});

it('can delete unused asset', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->success()->create();

    $this->actingAs($user)->deleteJson('/v1/assets/'.$asset->id)->assertNoContent();

    $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
});

it('cannot delete asset that is used in a video', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create();

    $faceless = Faceless::factory()->for($user)->create();
    $faceless->assets()->attach($asset, ['order' => 0]);

    $this->actingAs($user)->deleteJson('/v1/assets/'.$asset->id)
        ->assertBadRequest()
        ->assertJsonPath('message', 'Cannot delete an asset that is actively used in the video.');

    $this->assertDatabaseHas('assets', ['id' => $asset->id]);
});

it('cant delete an asset which is in processing state', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->processing()->create();

    $this->actingAs($user)->deleteJson('/v1/assets/'.$asset->id)
        ->assertBadRequest()
        ->assertJsonPath('message', 'Cannot delete an asset that is currently processing.');

    $this->assertDatabaseHas('assets', ['id' => $asset->id]);
});

it('prevents unauthorized users from deleting assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $asset = Asset::factory()->recycle($user)->withMedia()->create();
    $faceless->assets()->attach($asset->id, ['order' => 0]);

    $this->actingAs(User::factory()->create())
        ->deleteJson("/v1/assets/{$asset->id}")
        ->assertForbidden();

    $this->assertModelExists($asset);

    $this->assertDatabaseHas('video_assets', [
        'asset_id' => $asset->id,
        'model_id' => $faceless->id,
    ]);
});

it('returns 404 for non-existent asset', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson('/v1/assets/99999')
        ->assertNotFound();
});

it('can show a single asset with media', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->withMedia()->create();

    $this->actingAs($user)->getJson('/v1/assets/'.$asset->id)
        ->assertOk()
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'media' => [
                    '*' => ['id', 'name', 'mime_type', 'size'],
                ],
            ],
        ]);
});

it('can show a single asset with optional includes', function () {
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create(['genre_id' => $genre->id]);

    $this->actingAs($user)->getJson('/v1/assets/'.$asset->id.'?include=genre,user')
        ->assertOk()
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonStructure([
            'data' => [
                'id',
                'genre' => ['id', 'name', 'slug'],
                'user' => ['id', 'name', 'email'],
            ],
        ]);
});

it('cannot show asset of another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($otherUser)->create();

    $this->actingAs($user)->getJson('/v1/assets/'.$asset->id)
        ->assertForbidden();
});

it('can update asset name', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user)->patchJson('/v1/assets/'.$asset->id, [
        'name' => 'New Asset Name',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonPath('data.name', 'New Asset Name');

    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'name' => 'New Asset Name',
    ]);
});

it('validates asset name is required', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create();

    $this->actingAs($user)->patchJson('/v1/assets/'.$asset->id, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('cannot update asset of another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($otherUser)->create();

    $this->actingAs($user)->patchJson('/v1/assets/'.$asset->id, [
        'name' => 'New Name',
    ])
        ->assertForbidden();
});

it('shows bookmark status in asset details', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->for($user)->withMedia()->create();

    $asset->bookmarks()->create(['user_id' => $user->id]);

    $this->actingAs($user)->getJson("/v1/assets/{$asset->id}")
        ->assertOk()
        ->assertJsonPath('data.is_bookmarked', true);
});

it('shows is_used as true in asset show endpoint when asset is used in videos', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create();
    $faceless = Faceless::factory()->for($user)->create();
    $faceless->assets()->attach($asset, ['order' => 0]);

    $this->actingAs($user)->getJson("/v1/assets/{$asset->id}")
        ->assertOk()
        ->assertJsonPath('data.is_used', true);
});

it('shows is_used as false in asset show endpoint when asset is not used in videos', function () {
    $user = User::factory()->create();

    $asset = Asset::factory()->aiVideo()->for($user)->create();

    $response = $this->actingAs($user)->getJson("/v1/assets/{$asset->id}")->assertOk();

    $response->assertJsonStructure(['data' => ['is_used']]);
    $response->assertJsonPath('data.is_used', false);
});

it('can bulk delete assets successfully', function () {
    Queue::fake();
    $user = User::factory()->create();

    $assets = Asset::factory(5)->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $assetIds = $assets->pluck('id')->toArray();

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => $assetIds,
    ]);

    $response->assertAccepted()
        ->assertJson([
            'data' => [
                'message' => 'Assets have been queued for deletion.',
            ],
        ]);

    Queue::assertPushed(DeleteBulkAssetsJob::class, function ($job) use ($assetIds, $user) {
        return collect($job->assets)->diff($assetIds)->isEmpty() && $job->user->is($user);
    });
});

it('cannot delete assets owned by other users', function () {
    Queue::fake();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $own = Asset::factory(2)->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $other = Asset::factory(2)->create([
        'user_id' => $otherUser->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $allAssetIds = array_merge(
        $own->pluck('id')->toArray(),
        $other->pluck('id')->toArray()
    );

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => $allAssetIds,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);
});

it('fails validation when assets are used in videos', function () {
    Queue::fake();
    $user = User::factory()->create();

    $unused = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $used = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $faceless = Faceless::factory()->create(['user_id' => $user->id]);

    $faceless->assets()->attach($used->id, [
        'order' => 1,
        'active' => true,
    ]);

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => [$unused->id, $used->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);

    Queue::assertNotPushed(DeleteBulkAssetsJob::class);
});

it('fails when processing assets are included', function () {
    Queue::fake();
    $user = User::factory()->create();

    $ready = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $processing = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::PROCESSING,
    ]);

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => [$ready->id, $processing->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);
});

it('fails validation without assets', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);
});

it('fails validation with invalid assets', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => ['not-a-number', 'invalid'],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets.0', 'assets.1']);
});

it('fails validation with too many assets', function () {
    $user = User::factory()->create();

    $assetIds = range(1, 101); // 101 assets

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => $assetIds,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);
});

it('fails validation with duplicate assets', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => [1, 2, 1, 3], // Duplicate ID 1
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets.2']);
});

it('fails validation when no assets found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => [999, 1000], // Non-existent IDs
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);
});

it('fails when all assets are invalid', function () {
    Queue::fake();
    $user = User::factory()->create();

    $processing = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::PROCESSING,
    ]);

    $used = Asset::factory()->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $faceless = Faceless::factory()->create(['user_id' => $user->id]);

    $faceless->assets()->attach($used->id, [
        'order' => 1,
        'active' => true,
    ]);

    $response = $this->actingAs($user)->deleteJson('/v1/assets/bulk', [
        'assets' => [$processing->id, $used->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['assets']);

    Queue::assertNotPushed(DeleteBulkAssetsJob::class);
});

it('requires authentication for bulk delete', function () {
    $response = $this->deleteJson('/v1/assets/bulk', [
        'assets' => [1, 2, 3],
    ]);

    $response->assertUnauthorized();
});
