<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Jobs\DeleteBulkAssetsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes valid assets in bulk', function () {
    $user = User::factory()->create();

    $assets = Asset::factory(5)->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $assetIds = $assets->pluck('id')->toArray();

    $job = new DeleteBulkAssetsJob($assetIds, $user);
    $job->handle();

    foreach ($assetIds as $assetId) {
        $this->assertDatabaseMissing('assets', ['id' => $assetId]);
    }
});

it('only deletes assets owned by the specified user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userAssets = Asset::factory(3)->create([
        'user_id' => $user->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $otherUserAssets = Asset::factory(2)->create([
        'user_id' => $otherUser->id,
        'status' => AssetStatus::SUCCESS,
    ]);

    $allAssetIds = array_merge(
        $userAssets->pluck('id')->toArray(),
        $otherUserAssets->pluck('id')->toArray()
    );

    $job = new DeleteBulkAssetsJob($allAssetIds, $user);
    $job->handle();

    // User's assets should be deleted
    foreach ($userAssets as $asset) {
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    // Other user's assets should remain
    foreach ($otherUserAssets as $asset) {
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }
});

it('does not delete assets that are processing', function () {
    $user = User::factory()->create();

    [$success, $processing, $failed] = Asset::factory(3)
        ->for($user)
        ->forEachSequence(
            ['status' => AssetStatus::SUCCESS],
            ['status' => AssetStatus::PROCESSING],
            ['status' => AssetStatus::FAILED]
        )
        ->create();

    $assetIds = [
        $success->id,
        $processing->id,
        $failed->id,
    ];

    $job = new DeleteBulkAssetsJob($assetIds, $user);
    $job->handle();

    // Success and failed assets should be deleted
    $this->assertDatabaseMissing('assets', ['id' => $success->id]);
    $this->assertDatabaseMissing('assets', ['id' => $failed->id]);

    // Processing asset should remain
    $this->assertDatabaseHas('assets', ['id' => $processing->id]);
});

it('does not delete assets that are used in videos', function () {
    $user = User::factory()->create();

    [$unused, $used] = Asset::factory(2)
        ->for($user)
        ->success()
        ->create();

    $faceless = Faceless::factory()->create(['user_id' => $user->id]);
    $faceless->assets()->attach($used->id, [
        'order' => 1,
        'active' => true,
    ]);

    $assetIds = [$unused->id, $used->id];

    $job = new DeleteBulkAssetsJob($assetIds, $user);
    $job->handle();

    // Unused asset should be deleted
    $this->assertDatabaseMissing('assets', ['id' => $unused->id]);

    // Used asset should remain
    $this->assertDatabaseHas('assets', ['id' => $used->id]);
});

it('applies all filters correctly in combination', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    [$valid, $processing, $used] = Asset::factory(3)
        ->for($user)
        ->forEachSequence(
            ['status' => AssetStatus::SUCCESS],
            ['status' => AssetStatus::PROCESSING],
            ['status' => AssetStatus::SUCCESS]
        )
        ->create();

    $wrongUser = Asset::factory()
        ->for($otherUser)
        ->success()
        ->create();

    $faceless = Faceless::factory()->create(['user_id' => $user->id]);
    $faceless->assets()->attach($used->id, ['order' => 1]);

    $assetIds = [
        $valid->id,
        $wrongUser->id,
        $processing->id,
        $used->id,
    ];

    $job = new DeleteBulkAssetsJob($assetIds, $user);
    $job->handle();

    $this->assertDatabaseMissing('assets', ['id' => $valid->id]);

    $this->assertDatabaseHas('assets', ['id' => $wrongUser->id]);
    $this->assertDatabaseHas('assets', ['id' => $processing->id]);
    $this->assertDatabaseHas('assets', ['id' => $used->id]);
});
