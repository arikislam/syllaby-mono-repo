<?php

namespace Tests\Feature\Users;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use Illuminate\Support\Number;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Cache;
use App\Syllaby\Assets\Enums\AssetType;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    Cache::flush(); // For deterministic tests
});

it('it shows the current used storage for a user', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);

    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $totalStorage = (int) $plan->details('features.storage');

    $john = User::factory()->create();
    $random = Video::factory()->for($john)->create();
    Media::factory()->for($random, 'model')->create([
        'size' => 1000,
        'user_id' => $john->id,
    ]);

    $video = Video::factory()->for($user)->create();
    Media::factory()->for($video, 'model')->create([
        'size' => 250_000,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage');

    $response->assertJsonFragment([
        'used' => ['raw' => 250_000, 'formatted' => Number::fileSize(250_000)],
        'total' => ['raw' => $totalStorage, 'formatted' => Number::fileSize($totalStorage)],
    ]);
});

it('it shows the storage breakdown for a user', function () {
    $product = Plan::factory()->product()->create();

    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);

    config(['syllaby.plans.basic.product_id' => $product->plan_id]);

    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $video = Video::factory()->for($user)->create();

    Media::factory()->for($video, 'model')->create([
        'size' => $videoSize = 500_000,
        'user_id' => $user->id,
    ]);

    $thumbnail = Asset::factory()->for($user)->create(['type' => AssetType::THUMBNAIL]);

    Media::factory()->for($thumbnail, 'model')->create([
        'size' => $thumbnailSize = 10_000,
        'user_id' => $user->id,
    ]);

    $aiImage = Asset::factory()->for($user)->aiImage()->create();

    Media::factory()->for($aiImage, 'model')->create([
        'size' => $aiImageSize = 20_000,
        'user_id' => $user->id,
    ]);

    $aiVideo = Asset::factory()->for($user)->aiVideo()->create();

    Media::factory()->for($aiVideo, 'model')->create([
        'size' => $aiVideoSize = 30_000,
        'user_id' => $user->id,
    ]);

    $customImage = Asset::factory()->for($user)->create(['type' => AssetType::CUSTOM_IMAGE]);

    Media::factory()->for($customImage, 'model')->create([
        'size' => $customImageSize = 40_000,
        'user_id' => $user->id,
    ]);

    $stockVideo = Asset::factory()->for($user)->create(['type' => AssetType::STOCK_VIDEO]);

    Media::factory()->for($stockVideo, 'model')->create([
        'size' => $stockVideoSize = 50_000,
        'user_id' => $user->id,
    ]);

    $totalUsed = $videoSize + $thumbnailSize + $aiImageSize + $aiVideoSize + $customImageSize + $stockVideoSize;

    $totalStorage = (int) $plan->details('features.storage');

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonFragment([
        'usage_percentage' => round(($totalUsed / $totalStorage) * 100, 2),
    ]);

    $response->assertJsonFragment([
        'available' => [
            'raw' => $available = max(0, $totalStorage - $totalUsed),
            'formatted' => Number::fileSize($available),
        ],
    ]);

    $response->assertJsonPath('data.breakdown.videos', [
        'label' => 'Videos',
        'raw' => $videoSize,
        'formatted' => Number::fileSize($videoSize),
    ]);

    $response->assertJsonPath('data.breakdown.thumbnail', [
        'label' => 'Thumbnails',
        'raw' => $thumbnailSize,
        'formatted' => Number::fileSize($thumbnailSize),
    ]);

    $response->assertJsonPath('data.breakdown.ai-image', [
        'label' => 'AI Generated Images',
        'raw' => $aiImageSize,
        'formatted' => Number::fileSize($aiImageSize),
    ]);

    $response->assertJsonPath('data.breakdown.motion-videos', [
        'label' => 'Motion Videos',
        'raw' => $aiVideoSize,
        'formatted' => Number::fileSize($aiVideoSize),
    ]);

    $response->assertJsonPath('data.breakdown.uploaded-media', [
        'label' => 'Uploaded Media',
        'raw' => $customImageSize,
        'formatted' => Number::fileSize($customImageSize),
    ]);

    $response->assertJsonPath('data.breakdown.stock-media', [
        'label' => 'Stock Media',
        'raw' => $stockVideoSize,
        'formatted' => Number::fileSize($stockVideoSize),
    ]);
});

it('does not return breakdown without include parameter for backward compatibility', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $video = Video::factory()->for($user)->create();

    Media::factory()->for($video, 'model')->create([
        'size' => 100_000,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage');

    $response->assertJsonStructure([
        'data' => [
            'used' => ['raw', 'formatted'],
            'total' => ['raw', 'formatted'],
            'base' => ['raw', 'formatted'],
            'extra' => ['raw', 'formatted'],
        ],
    ]);

    $data = $response->json('data');

    expect($data)->not->toHaveKey('breakdown')
        ->and($data)->not->toHaveKey('usage_percentage')
        ->and($data)->not->toHaveKey('available');
});

it('combines motion videos from ai-video and faceless-background assets', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $aiVideo = Asset::factory()->for($user)->create(['type' => AssetType::AI_VIDEO]);

    Media::factory()->for($aiVideo, 'model')->create([
        'size' => $aiVideoSize = 300_000,
        'user_id' => $user->id,
    ]);

    $facelessBg = Asset::factory()->for($user)->create(['type' => AssetType::FACELESS_BACKGROUND]);

    Media::factory()->for($facelessBg, 'model')->create([
        'size' => $facelessBgSize = 200_000,
        'user_id' => $user->id,
    ]);

    $totalMotionVideos = $aiVideoSize + $facelessBgSize;

    $this->actingAs($user, 'sanctum');

    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonPath('data.breakdown.motion-videos', [
        'label' => 'Motion Videos',
        'raw' => $totalMotionVideos,
        'formatted' => Number::fileSize($totalMotionVideos),
    ]);
});

it('combines uploaded media from custom images and videos', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $customImage = Asset::factory()->for($user)->create(['type' => AssetType::CUSTOM_IMAGE]);

    Media::factory()->for($customImage, 'model')->create([
        'size' => $customImageSize = 150_000,
        'user_id' => $user->id,
    ]);

    $customVideo = Asset::factory()->for($user)->create(['type' => AssetType::CUSTOM_VIDEO]);

    Media::factory()->for($customVideo, 'model')->create([
        'size' => $customVideoSize = 250_000,
        'user_id' => $user->id,
    ]);

    $totalUploaded = $customImageSize + $customVideoSize;

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonPath('data.breakdown.uploaded-media', [
        'label' => 'Uploaded Media',
        'raw' => $totalUploaded,
        'formatted' => Number::fileSize($totalUploaded),
    ]);
});

it('combines stock media from stock images and videos', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $stockImage = Asset::factory()->for($user)->create(['type' => AssetType::STOCK_IMAGE]);

    Media::factory()->for($stockImage, 'model')->create([
        'size' => $stockImageSize = 80_000,
        'user_id' => $user->id,
    ]);

    $stockVideo = Asset::factory()->for($user)->create(['type' => AssetType::STOCK_VIDEO]);

    Media::factory()->for($stockVideo, 'model')->create([
        'size' => $stockVideoSize = 120_000,
        'user_id' => $user->id,
    ]);

    $totalStock = $stockImageSize + $stockVideoSize;

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonPath('data.breakdown.stock-media', [
        'label' => 'Stock Media',
        'raw' => $totalStock,
        'formatted' => Number::fileSize($totalStock),
    ]);
});

it('includes all categories with zero values in breakdown', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $video = Video::factory()->for($user)->create();

    Media::factory()->for($video, 'model')->create([
        'size' => 500_000,
        'user_id' => $user->id,
    ]);

    $thumbnail = Asset::factory()->for($user)->create(['type' => AssetType::THUMBNAIL]);

    Media::factory()->for($thumbnail, 'model')->create([
        'size' => 100_000,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $breakdown = $response->json('data.breakdown');

    // Should have all categories
    expect(count($breakdown))->toBe(6);

    // Verify used categories have correct values
    $response->assertJsonPath('data.breakdown.videos.raw', 500_000);
    $response->assertJsonPath('data.breakdown.thumbnail.raw', 100_000);

    // Verify empty categories have 0 values
    $response->assertJsonPath('data.breakdown.ai-image.raw', 0);
    $response->assertJsonPath('data.breakdown.motion-videos.raw', 0);
    $response->assertJsonPath('data.breakdown.uploaded-media.raw', 0);
    $response->assertJsonPath('data.breakdown.stock-media.raw', 0);
});

it('only includes authenticated user storage in breakdown', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();
    $otherUser = User::factory()->create();

    $userVideo = Video::factory()->for($user)->create();

    Media::factory()->for($userVideo, 'model')->create([
        'size' => 300_000,
        'user_id' => $user->id,
    ]);

    $otherVideo = Video::factory()->for($otherUser)->create();

    Media::factory()->for($otherVideo, 'model')->create([
        'size' => 500_000,
        'user_id' => $otherUser->id,
    ]);

    $userAsset = Asset::factory()->for($user)->create(['type' => AssetType::AI_IMAGE]);

    Media::factory()->for($userAsset, 'model')->create([
        'size' => 100_000,
        'user_id' => $user->id,
    ]);

    $otherAsset = Asset::factory()->for($otherUser)->create(['type' => AssetType::AI_IMAGE]);

    Media::factory()->for($otherAsset, 'model')->create([
        'size' => 200_000,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonFragment([
        'used' => ['raw' => 400_000, 'formatted' => Number::fileSize(400_000)],
    ]);

    $response->assertJsonPath('data.breakdown.videos.raw', 300_000);
    $response->assertJsonPath('data.breakdown.ai-image.raw', 100_000);

    $breakdown = $response->json('data.breakdown');
    $totalInBreakdown = array_sum(array_column($breakdown, 'raw'));

    expect($totalInBreakdown)->toBe(400_000);
});

it('correctly sums multiple media files per asset', function () {
    $product = Plan::factory()->product()->create();
    $plan = Plan::factory()->price()->create(['parent_id' => $product->id]);
    config(['syllaby.plans.basic.product_id' => $product->plan_id]);
    Feature::define('max_storage', $plan->details('features.storage'));

    $user = User::factory()->withSubscription($plan)->create();

    $aiImage = Asset::factory()->for($user)->create(['type' => AssetType::AI_IMAGE]);

    Media::factory()->for($aiImage, 'model')->create([
        'size' => 100_000,
        'user_id' => $user->id,
        'collection_name' => 'default',
    ]);

    Media::factory()->for($aiImage, 'model')->create([
        'size' => 150_000,
        'user_id' => $user->id,
        'collection_name' => 'conversions',
    ]);

    Media::factory()->for($aiImage, 'model')->create([
        'size' => 50_000,
        'user_id' => $user->id,
        'collection_name' => 'thumbnails',
    ]);

    $totalAiImageSize = 100_000 + 150_000 + 50_000;

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/storage?include=breakdown');

    $response->assertJsonPath('data.breakdown.ai-image', [
        'label' => 'AI Generated Images',
        'raw' => $totalAiImageSize,
        'formatted' => Number::fileSize($totalAiImageSize),
    ]);
});
