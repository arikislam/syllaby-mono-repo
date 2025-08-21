<?php

namespace Tests\Feature\Videos;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Metadata\Caption;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Metadata\Timeline;
use App\Syllaby\Generators\Generator;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('cant fetch videos while unauthenticated', function () {
    $this->getJson('v1/videos')->assertUnauthorized();
});

it('can fetch paginated videos', function () {
    $user = User::factory()->create();
    Video::factory()->for($user)->count(4)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?per_page=2&page=2');

    expect($response->json('data'))->toHaveCount(2);
});

it('can filter videos by title', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3] = Video::factory()->for($user)->count(3)->sequence(
        ['title' => 'Hello World'],
        ['title' => 'Lorem Ipsum'],
        ['title' => 'Some Different Title'],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?filter[title]=Hello,Ipsum');

    expect($response->json('data'))->toHaveCount(2)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v2->id),
    );
});

it('can sort videos by title ascending', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3] = Video::factory()->for($user)->count(3)->sequence(
        ['title' => 'A'],
        ['title' => 'B'],
        ['title' => 'C'],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?sort=title');

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v2->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v3->id),
    );
});

it('can sort videos by title descending', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3] = Video::factory()->for($user)->count(3)->sequence(
        ['title' => 'A'],
        ['title' => 'B'],
        ['title' => 'C'],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?sort=-title');

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v3->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v2->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
    );
});

it('can filter videos by status', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3, $v4] = Video::factory()->for($user)->count(4)->sequence(
        ['status' => VideoStatus::DRAFT, 'type' => Video::FACELESS],
        ['status' => VideoStatus::COMPLETED, 'type' => Video::CUSTOM],
        ['status' => VideoStatus::FAILED, 'type' => Video::CUSTOM],
        ['status' => VideoStatus::RENDERING, 'type' => Video::FACELESS],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?filter[status]=completed,draft,failed')->assertOk();

    expect($response->json('data'))->toHaveCount(2)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v2->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v3->id),
    );
});

it('can filter videos by type', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3, $v4] = Video::factory()->for($user)->count(4)->sequence(
        ['type' => 'custom'],
        ['type' => 'faceless'],
        ['type' => 'custom'],
        ['type' => 'custom'],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?filter[type]=custom');

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v3->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v4->id),
    );
});

it('can filter videos by both status and title', function () {
    $user = User::factory()->create();

    [$v1, $v2, $v3, $v4] = Video::factory()->for($user)->count(4)->sequence(
        ['title' => 'Hello World', 'status' => VideoStatus::DRAFT, 'type' => Video::CUSTOM],
        ['title' => 'Lorem Ipsum', 'status' => VideoStatus::COMPLETED, 'type' => Video::FACELESS],
        ['title' => 'Some Different Title', 'status' => VideoStatus::FAILED, 'type' => Video::CUSTOM],
        ['title' => 'Yet another video', 'status' => VideoStatus::RENDERING, 'type' => Video::FACELESS],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?filter[status]=draft,rendering&filter[title]=Hello')->assertOk();

    expect($response->json('data'))->toHaveCount(1)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
    );
});

it('can sort the list of videos by date', function () {
    $user = User::factory()->create();

    Carbon::setTestNow(now());

    [$v1, $v2, $v3] = Video::factory()->for($user)->count(3)->sequence(
        ['updated_at' => now()->subDay()],
        ['updated_at' => now()->subDays(2)],
        ['updated_at' => now()->subDays(3)],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/videos?sort=date');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v3->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v2->id),
        fn ($video) => expect(Arr::get($video->value, 'id'))->toBe($v1->id),
    );
});

it('can fetch a single video', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/videos/{$video->id}");

    expect($response->json('data'))
        ->id->toBe($video->id)
        ->user_id->toBe($user->id);
});

it('can fetch a single video with includes', function () {
    $user = User::factory()->create();

    $video = Video::factory()->for($user)->create();
    $footage = Footage::factory()->for($video)->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/videos/{$video->id}?include=footage");

    expect($response->json('data'))
        ->id->toBe($video->id)
        ->user_id->toBe($user->id)
        ->and($response->json('data.footage'))
        ->id->toBe($footage->id);
});

it('fails to fetch a another user video', function () {
    $user = User::factory()->create();
    $video = Video::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/videos/{$video->id}");

    expect($response->json('data'))->toBe(null);
});

it('update a video details', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $video = Video::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/videos/{$video->id}", [
        'title' => 'Amazing video',
    ]);

    $response->assertOk();
    expect($response->json('data'))
        ->id->toBe($video->id)
        ->title->toBe('Amazing video');
});

it('fails to update a video details from another user', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $video = Video::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/videos/{$video->id}", [
        'title' => 'Amazing video',
    ]);

    $response->assertForbidden();
});

it('allows to delete a custom video and its dependencies', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->completed()->create([
        'type' => Video::CUSTOM,
    ]);

    $footage = Footage::factory()->for($video)->create();
    Timeline::factory()->for($footage, 'model')->for($user)->create();

    $clone = RealClone::factory()->for($footage)->create();
    Generator::factory()->for($clone, 'model')->create();

    $this->assertDatabaseCount('videos', 1);
    $this->assertDatabaseCount('footages', 1);
    $this->assertDatabaseCount('generators', 1);
    $this->assertDatabaseCount('timelines', 1);
    $this->assertDatabaseCount('real_clones', 1);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertAccepted();

    $this->assertDatabaseCount('videos', 0);
    $this->assertDatabaseCount('footages', 0);
    $this->assertDatabaseCount('timelines', 0);
    $this->assertDatabaseCount('generators', 0);
    $this->assertDatabaseCount('real_clones', 0);
});

it('allows to delete a faceless video and its dependencies', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->completed()->create([
        'type' => Video::FACELESS,
    ]);

    $faceless = Faceless::factory()->for($video)->create();
    Caption::factory()->for($faceless, 'model')->for($user)->create();
    Timeline::factory()->for($faceless, 'model')->for($user)->create();

    $this->assertDatabaseCount('videos', 1);
    $this->assertDatabaseCount('facelesses', 1);
    $this->assertDatabaseCount('timelines', 1);
    $this->assertDatabaseCount('captions', 1);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertAccepted();

    $this->assertDatabaseCount('videos', 0);
    $this->assertDatabaseCount('facelesses', 0);
    $this->assertDatabaseCount('timelines', 0);
    $this->assertDatabaseCount('captions', 0);
});

it('fails to delete a video that is still rendering', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->rendering()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertForbidden();
});

it('fails to delete a video that is still syncing', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->syncing()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertForbidden();
});

it('fails to delete a video that has real clones generating', function () {
    $user = User::factory()->create();

    $clone = RealClone::factory()->recycle($user)->generating()->create();
    $video = $clone->footage->video;

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertForbidden();
});

it('fails to delete a video from another user', function () {
    $user = User::factory()->create();
    $video = Video::factory()->draft()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("/v1/videos/{$video->id}");

    $response->assertForbidden();
});

it('can fetch published videos of user', function () {
    $user = User::factory()->create();

    $published = Video::factory()->for($user)->completed()->create();

    SocialChannel::factory()->recycle($user)
        ->for(SocialAccount::factory()->createQuietly(), 'account')
        ->hasAttached(Publication::factory()->permanent()->state(['video_id' => $published->id]), [
            'status' => SocialUploadStatus::COMPLETED,
            'provider_media_id' => '123',
            'post_type' => PostType::POST->value,
        ])->create();

    Video::factory()->for($user)->completed()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/v1/videos?filter[published]=true')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id);
});

it('excludes draft publications from published videos', function () {
    $user = User::factory()->create();

    $published = Video::factory()->for($user)->completed()->create();

    SocialChannel::factory()->recycle($user)
        ->for(SocialAccount::factory()->createQuietly(), 'account')
        ->hasAttached(Publication::factory()->state(['video_id' => $published->id, 'draft' => true]), [
            'status' => SocialUploadStatus::DRAFT->value,
            'provider_media_id' => '123',
            'post_type' => PostType::POST->value,
        ])->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/v1/videos?filter[published]=true')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('can fetch videos of a specific folder', function () {
    $user = User::factory()->create();

    $default = Folder::factory()->recycle($user)->create(['name' => 'Default']);

    $video = Video::factory()->for($user)->create();
    $video->resource()->create(['parent_id' => $default->resource->id, 'user_id' => $user->id]);

    Video::factory(10)->recycle($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/v1/videos?folder={$default->resource->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $video->id);
});

it('detaches assets of facelesss video on deletion', function () {
    $user = User::factory()->create();

    $video = Video::factory()->for($user)->faceless()->completed()->create();

    $faceless = Faceless::factory()->for($user)->for($video)->ai()->create();
    $faceless->assets()->attach($asset = Asset::factory()->create(), ['order' => 1, 'active' => true]);

    $this->actingAs($user, 'sanctum')->deleteJson("/v1/videos/{$faceless->video->id}");

    $this->assertDatabaseCount('video_assets', 0);
    $this->assertDatabaseHas('assets', ['id' => $asset->id]);
});

it('can delete unused assets of faceless video on deletion', function () {
    $user = User::factory()->create();

    [$asset1, $asset2] = Asset::factory(2)->aiVideo()->for($user)->create();
    [$video1, $video2] = Video::factory(2)->for($user)->faceless()->completed()->create();

    $faceless1 = Faceless::factory()->for($user)->for($video1)->ai()->create();
    $faceless1->assets()->attach($asset1, ['order' => 1, 'active' => true]);
    $faceless1->assets()->attach($asset2, ['order' => 2, 'active' => true]);

    $faceless2 = Faceless::factory()->for($user)->for($video2)->ai()->create();
    $faceless2->assets()->attach($asset2, ['order' => 1, 'active' => true]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/v1/videos/{$faceless1->video->id}?delete_unused_assets=".true)
        ->assertAccepted();

    $this->assertDatabaseMissing('assets', ['id' => $asset1->id]);
    $this->assertDatabaseMissing('video_assets', [
        'model_id' => $faceless1->id,
        'model_type' => $faceless1->getMorphClass(),
        'asset_id' => $asset1->id,
    ]);

    $this->assertDatabaseMissing('video_assets', [
        'model_id' => $faceless1->id,
        'model_type' => $faceless1->getMorphClass(),
        'asset_id' => $asset2->id,
    ]);

    $this->assertDatabaseHas('assets', ['id' => $asset2->id]);
    $this->assertDatabaseHas('video_assets', [
        'model_id' => $faceless2->id,
        'model_type' => $faceless2->getMorphClass(),
        'asset_id' => $asset2->id,
    ]);
});

it('can filter videos by single genre', function () {
    $user = User::factory()->create();

    [$action, $comedy, $drama] = Genre::factory()->forEachSequence(
        ['slug' => 'action', 'name' => 'Action'],
        ['slug' => 'comedy', 'name' => 'Comedy'],
        ['slug' => 'drama', 'name' => 'Drama'],
    )->create();

    [$video1, $video2, $video3] = Video::factory(3)->for($user)->faceless()->create();
    
    Faceless::factory()->forEachSequence(
        ['video_id' => $video1->id, 'user_id' => $user->id, 'genre_id' => $action->id],
        ['video_id' => $video2->id, 'user_id' => $user->id, 'genre_id' => $comedy->id],
        ['video_id' => $video3->id, 'user_id' => $user->id, 'genre_id' => $drama->id],
    )->create();

    // Create a video without faceless (should not appear in genre filter)
    Video::factory()->for($user)->custom()->create();

    $this->actingAs($user, 'sanctum');
    
    $response = $this->getJson('/v1/videos?filter[genre]=action');
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($video1->id);
});

it('can filter videos by multiple genres', function () {
    $user = User::factory()->create();

    [$action, $comedy, $drama] = Genre::factory()->forEachSequence(
        ['slug' => 'action', 'name' => 'Action'],
        ['slug' => 'comedy', 'name' => 'Comedy'],
        ['slug' => 'drama', 'name' => 'Drama'],
    )->create();

    [$video1, $video2, $video3] = Video::factory(3)->for($user)->faceless()->create();
    
    Faceless::factory()->forEachSequence(
        ['video_id' => $video1->id, 'user_id' => $user->id, 'genre_id' => $action->id],
        ['video_id' => $video2->id, 'user_id' => $user->id, 'genre_id' => $comedy->id],
        ['video_id' => $video3->id, 'user_id' => $user->id, 'genre_id' => $drama->id],
    )->create();

    $this->actingAs($user, 'sanctum');
    
    $response = $this->getJson('/v1/videos?filter[genre]=action,comedy');

    expect($response->json('data'))->toHaveCount(2);
    $ids = collect($response->json('data'))->pluck('id')->toArray();

    expect($ids)->toContain($video1->id)
        ->and($ids)->toContain($video2->id);
});

it('does not filter real clone videos by genre', function () {
    $user = User::factory()->create();
    
    $action = Genre::factory()->create(['slug' => 'action', 'name' => 'Action']);

    $video = Video::factory()->for($user)->custom()->create();
    $footage = Footage::factory()->for($user)->for($video)->create();

    RealClone::factory()->for($user)->for($footage)->create();

    $faceless = Video::factory()->for($user)->faceless()->create();
    Faceless::factory()->for($user)->for($faceless)->create(['genre_id' => $action->id]);

    $this->actingAs($user, 'sanctum');
    
    $response = $this->getJson('/v1/videos?filter[genre]=action');

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($faceless->id);
});

it('returns empty result when filtering by non-existent genre', function () {
    $user = User::factory()->create();
    
    $video = Video::factory()->for($user)->faceless()->create();
    Faceless::factory()->for($user)->for($video)->create();

    $this->actingAs($user, 'sanctum');
    
    $response = $this->getJson('/v1/videos?filter[genre]=nonexistent');
    expect($response->json('data'))->toHaveCount(0);
});

it('can combine genre filter with other filters', function () {
    $user = User::factory()->create();
    
    $action = Genre::factory()->create(['slug' => 'action', 'name' => 'Action']);

    [$video1, $video2, $video3] = Video::factory()->for($user)->faceless()->forEachSequence(
        ['status' => VideoStatus::COMPLETED],
        ['status' => VideoStatus::DRAFT],
        ['status' => VideoStatus::COMPLETED],
    )->create();

    Faceless::factory()->forEachSequence(
        ['video_id' => $video1->id, 'user_id' => $user->id, 'genre_id' => $action->id],
        ['video_id' => $video2->id, 'user_id' => $user->id, 'genre_id' => $action->id],
        ['video_id' => $video3->id, 'user_id' => $user->id, 'genre_id' => null], // No genre
    )->create();

    $this->actingAs($user, 'sanctum');
    
    $response = $this->getJson('/v1/videos?filter[genre]=action&filter[status]=completed');
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($video1->id);
});
