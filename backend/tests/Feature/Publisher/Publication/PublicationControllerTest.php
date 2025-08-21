<?php

namespace Tests\Feature\Publisher\Publication;

use Feature;
use Carbon\Carbon;
use Tests\TestCase;
use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Videos\Faceless;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\MetricsTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Metrics\AggregateType;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\PostType;
use App\Syllaby\Publisher\Metrics\PublicationAggregate;
use App\Syllaby\Publisher\Metrics\PublicationMetricKey;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Bus::fake(PerformConversionsJob::class);
    Event::fake(MediaHasBeenAddedEvent::class);
});

it('can list publications', function () {
    $user = User::factory()->create();

    Publication::factory()->permanent()->count(5)->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')->getJson('v1/publications')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'scheduled_at', 'draft', 'temporary', 'media', 'accounts'],
            ],
        ]);
});

it('cant list publications if user doesnt own that', function () {
    $user = User::factory()->create();

    Publication::factory()->permanent()->for(User::factory()->create())->count(5);

    $this->actingAs($user, 'sanctum')->getJson('v1/publications')->assertOk()->assertJsonCount(0, 'data');
});

it('lists the publication in descending order by default', function () {
    Carbon::setTestNow('2023-01-10 00:00:00');

    $user = User::factory()->create();

    [$p1, $p2, $p3, $p4, $p5] = Publication::factory()->permanent()
        ->count(5)
        ->sequence(
            ['user_id' => $user->id, 'created_at' => now()->subDays(5)],
            ['user_id' => $user->id, 'created_at' => now()->subDays(4)],
            ['user_id' => $user->id, 'created_at' => now()->subDays(3)],
            ['user_id' => $user->id, 'created_at' => now()->subDays(2)],
            ['user_id' => $user->id, 'created_at' => now()->subDay()]
        )->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('v1/publications')->assertOk();

    expect($response->json('data'))
        ->and($response->json('data.0.id'))->toBe($p5->id)
        ->and($response->json('data.1.id'))->toBe($p4->id)
        ->and($response->json('data.2.id'))->toBe($p3->id)
        ->and($response->json('data.3.id'))->toBe($p2->id)
        ->and($response->json('data.4.id'))->toBe($p1->id);
});

it('can display media and accounts of a publication', function () {
    $user = User::factory()->create();

    $account = SocialAccount::factory()->tiktok()->create(['user_id' => $user->id]);

    $channel = SocialChannel::factory()->individual()->create(['social_account_id' => $account->id]);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $publication = Publication::factory()->create(['user_id' => $user->id]);

    $publication->addMedia($file)->toMediaCollection('publications');

    $publication->channels()->attach($channel, ['status' => SocialUploadStatus::PROCESSING->value, 'post_type' => PostType::POST->value]);

    $response = $this->actingAs($user, 'sanctum')->getJson("v1/publications/{$publication->id}?include=media,channels.account")->assertOk();

    expect($response->json('data'))
        ->media->toBeArray()->not->toBeEmpty()
        ->accounts->toBeArray()->not->toBeEmpty();
});

it('returns the script of a faceless video', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()
        ->for(Video::factory()->completed()->faceless()->for($user))
        ->create(['script' => $script = 'Lorem Ipsum is testing']);

    $this->actingAs($user, 'sanctum')
        ->postJson('v1/publications', ['video_id' => $faceless->video->id])
        ->assertJsonFragment(['script' => $script]);
});

it('returns the script of a editor video', function () {
    $user = User::factory()->create();

    $editor = Video::factory()->custom()->has(Footage::factory()->for($user)
        ->has(RealClone::factory()->for($user)->state(['script' => $script = 'Lorem Ipsum is testing']), 'clones'))
        ->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('v1/publications', ['video_id' => $editor->id])
        ->assertJsonFragment(['script' => $script]);
});

it('returns null for a video with no script', function () {
    $user = User::factory()->create();

    $editor = Video::factory()->custom()->has(Footage::factory()->for($user)
        ->has(RealClone::factory()->for($user)->state(['script' => null]), 'clones'))
        ->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('v1/publications', ['video_id' => $editor->id])
        ->assertJsonFragment(['script' => null]);
});

it('can paginate publications', function () {
    $user = User::factory()->create();

    Publication::factory()->permanent()->count(15)->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')->getJson('v1/publications')
        ->assertOk()
        ->assertJsonCount(12, 'data');

    $this->actingAs($user, 'sanctum')->getJson('v1/publications?page=2&per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can show a single publication', function () {
    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')->getJson("v1/publications/{$publication->id}?include=event,channels.account")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'name', 'scheduled_at', 'draft', 'temporary', 'media', 'accounts',
            ],
        ]);
});

it('cant show a single publication if user doesnt own that', function () {
    $user = User::factory()->create();

    $publication = Publication::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($user, 'sanctum')->getJson("v1/publications/{$publication->id}")->assertForbidden();
});

it('can delete a publication as well as accounts', function () {
    $user = User::factory()->create();

    $account = SocialAccount::factory()->tiktok()->create(['user_id' => $user->id]);

    $channel = SocialChannel::factory()->individual()->create(['social_account_id' => $account->id]);

    $publication = Publication::factory()->create(['user_id' => $user->id]);

    $publication->channels()->attach($channel, ['status' => SocialUploadStatus::PROCESSING->value]);

    $this->actingAs($user, 'sanctum')->deleteJson("v1/publications/{$publication->id}")->assertNoContent();

    $this->assertDatabaseMissing('publications', ['id' => $publication->id]);

    $this->assertDatabaseMissing('account_publications', [
        'social_channel_id' => $channel->id,
        'publication_id' => $publication->id,
    ]);
});

it('cant delete a publication if user doesnt own that', function () {
    $publication = Publication::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->deleteJson("v1/publications/{$publication->id}")
        ->assertForbidden();
});

it('can create a publication with video from editor', function () {
    Queue::fake();

    $user = User::factory()->create();

    $video = Video::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publications', [
        'video_id' => $video->id,
    ])->assertCreated();

    $this->assertDatabaseCount('publications', 1);

    $this->assertDatabaseHas('publications', [
        'user_id' => $user->id,
        'video_id' => $video->id,
        'draft' => true,
        'temporary' => true,
    ]);
});

it('can create a publication with custom video', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publications', [
        'files' => [
            $file = UploadedFile::fake()->create('video.mp4', 100),
        ],
    ])->assertCreated();

    $this->assertDatabaseCount('publications', 1);
    $this->assertDatabaseCount('media', 1);
    $this->assertDatabaseHas('publications', [
        'user_id' => $user->id,
        'draft' => true,
        'temporary' => true,
    ]);

    $media = $user->publications->first()->media->first();

    $this->assertTrue(Storage::disk('spaces')->exists($media->getPathRelativeToRoot()));

    $this->assertEquals($file->name, $media->file_name);
});

it('throws error if empty files are provided', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/publications', [
        'files' => [],
    ])->assertUnprocessable();
});

it('can update a publication with editor video', function () {
    $user = User::factory()->create();

    [$v1, $v2] = Video::factory()->count(2)->create(['user_id' => $user->id]);

    $publication = Publication::factory()->for($user)->for($v1)->create(['temporary' => false]);

    $this->actingAs($user, 'sanctum')->putJson("v1/publications/{$publication->id}", [
        'video_id' => $v2->id,
    ])->assertOk();

    $this->assertDatabaseCount('publications', 1);
    $this->assertDatabaseHas('publications', [
        'user_id' => $user->id,
        'video_id' => $v2->id,
        'draft' => true,
        'temporary' => false,
    ]);
});

it('can update a publication with custom video', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('video.mp4')->size(100);

    $publication = Publication::factory()->for($user)->create(['temporary' => false]);

    $publication->addMedia($file)->toMediaCollection('publications');

    $this->assertDatabaseCount('publications', 1);
    $this->assertDatabaseCount('media', 1);

    $this->actingAs($user, 'sanctum')->putJson("v1/publications/{$publication->id}", [
        'files' => [
            $file = UploadedFile::fake()->create('new-video.mp4', 100),
        ],
    ])->assertOk();

    $this->assertDatabaseCount('publications', 1);
    $this->assertDatabaseCount('media', 1);

    $media = $user->publications->first()->media->first();

    $this->assertTrue($media->file_name === $file->name);
});

it('can filter publications by status', function () {
    $user = User::factory()->create();

    $channel1 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::FAILED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $this->actingAs($user, 'sanctum')->getJson('v1/publications?filter[status]=completed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $channel1->publications->first()->id]);
});

it('can filter publications by multiple statuses', function () {
    $user = User::factory()->create();

    $channel1 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $channel2 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::FAILED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::SCHEDULED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $this->actingAs($user, 'sanctum')->getJson('v1/publications?filter[status]=completed,failed')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $channel1->publications->first()->id])
        ->assertJsonFragment(['id' => $channel2->publications->first()->id]);
});

it('just returns the pivots with specified status', function () {
    Carbon::setTestNow(now());

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();
    $publication = Publication::factory()->for($user)->permanent()->create();

    $channel->publications()->attach($publication->id, [
        'status' => SocialUploadStatus::SCHEDULED->value,
        'post_type' => PostType::POST->value,
        'created_at' => now()->subSeconds(2),
    ]);
    $channel->publications()->attach($publication->id, [
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => PostType::POST->value,
        'created_at' => now()->subSeconds(3),
    ]);
    $channel->publications()->attach($publication->id, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'post_type' => PostType::POST->value,
        'created_at' => now()->subSeconds(4),
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('v1/publications?filter[status]=completed,draft')->assertOk();

    expect($response->json('data.0.accounts'))
        ->toHaveCount(2)
        ->and($response->json('data.0.accounts.0.status'))->toBe(SocialUploadStatus::COMPLETED->value)
        ->and($response->json('data.0.accounts.1.status'))->toBe(SocialUploadStatus::DRAFT->value);
});

it('can filter publications by social-media providers', function () {
    $user = User::factory()->create();

    $channel1 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->youtube(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::SCHEDULED->value,
        'provider_media_id' => 'youtube-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $response = $this->actingAs($user, 'sanctum')->get('v1/publications?filter[channel]=tiktok')->assertOk();

    expect($response->json('data'))
        ->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($channel1->publications->first()->id);
});

it('can filter publications by multiple social-media providers', function () {
    $user = User::factory()->create();

    $channel1 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->linkedin(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'linkedin-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $channel2 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->youtube(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'youtube-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $this->actingAs($user, 'sanctum')->get('v1/publications?filter[channel]=tiktok,linkedin')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $channel1->publications->first()->id])
        ->assertJsonFragment(['id' => $channel2->publications->first()->id]);
});

it('can filter by status and social media provider', function () {
    $user = User::factory()->create();

    SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->linkedin(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'linkedin-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $channel2 = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached(Publication::factory()->for($user)->permanent(), [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $this->actingAs($user, 'sanctum')->get('v1/publications?filter[channel]=tiktok&filter[status]=completed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $channel2->publications->first()->id]);
});

it('returns error for invalid social media provider', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->get('v1/publications?filter[channel]=pinterest')->assertStatus(400);
});

it('gives combined metrics of all accounts with publications', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->tiktok(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'tiktok-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    $linkedin = SocialChannel::factory()->for(
        SocialAccount::factory()->for($user)->linkedin(), 'account'
    )->hasAttached($publication, [
        'status' => SocialUploadStatus::COMPLETED->value,
        'provider_media_id' => 'linkedin-dummy-id',
        'post_type' => PostType::POST->value,
    ])->create();

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 5000]);

    PublicationMetricValue::factory()->for($tiktok, 'channel')
        ->for($tiktok->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->create(['value' => 12]);

    PublicationMetricValue::factory()->for($linkedin, 'channel')
        ->for($linkedin->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'views-count')->first(), 'key')
        ->create(['value' => 4000]);

    PublicationMetricValue::factory()->for($linkedin, 'channel')
        ->for($linkedin->publications->first(), 'publication')
        ->for(PublicationMetricKey::where('slug', 'likes-count')->first(), 'key')
        ->create(['value' => 12]);

    $this->actingAs($user, 'sanctum')->getJson('v1/publications?include=aggregate')
        ->assertOk()
        ->assertJsonFragment([
            'aggregate' => [
                'views-count' => 9000,
                'likes-count' => 24,
                'comments-count' => 0,
            ],
        ]);
})->skip('Need to fix this test');

it('gives empty result when no metrics are found', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->has(Publication::factory()->permanent())->create();

    $this->actingAs($user, 'sanctum')->getJson('v1/publications?include=aggregate')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'aggregate' => [
                'views-count' => 0,
                'likes-count' => 0,
                'comments-count' => 0,
            ],
        ]);
});

it('deletes the event if scheduled publication is deleted', function () {
    Carbon::setTestNow('2024-01-01 00:00:00');

    $user = User::factory()->create();

    $publication = Publication::factory()->scheduled(now()->addDay())->permanent()->for($user)->create(['scheduled' => true]);

    $this->actingAs($user, 'sanctum')->deleteJson("v1/publications/{$publication->id}")->assertNoContent();

    $this->assertDatabaseEmpty('events');
    $this->assertDatabaseEmpty('publications');
});

it('can sort publications by views in ascending order', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    [$publication1, $publication2, $publication3] = Publication::factory(3)->permanent()->for($user)->create();

    [$channel1, $channel2, $channel3] = SocialChannel::factory(3)->for(SocialAccount::factory()->for($user)->tiktok(),
        'account')->create();

    $channel1->publications()->attach($publication1,
        ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication2,
        ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel3->publications()->attach($publication3,
        ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'views-count',
        'value' => 500,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication3->id,
        'social_channel_id' => $channel3->id,
        'key' => 'views-count',
        'value' => 300,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=views&include=aggregate')
        ->assertOk();

    // Check order: publication1 (100) -> publication3 (300) -> publication2 (500)
    expect($response->json('data.0.id'))->toBe($publication1->id)
        ->and($response->json('data.1.id'))->toBe($publication3->id)
        ->and($response->json('data.2.id'))->toBe($publication2->id);
});

it('can sort publications by views in descending order', function () {
    $this->seed(MetricsTableSeeder::class);
    $user = User::factory()->create();

    [$publication1, $publication2, $publication3] = Publication::factory(3)->permanent()->for($user)->create();

    [$channel1, $channel2, $channel3] = SocialChannel::factory(3)->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();

    $channel1->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel3->publications()->attach($publication3, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'views-count',
        'value' => 500,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication3->id,
        'social_channel_id' => $channel3->id,
        'key' => 'views-count',
        'value' => 300,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=-views&include=aggregate')
        ->assertOk();

    // Check order: publication2 (500) -> publication3 (300) -> publication1 (100)
    expect($response->json('data.0.id'))->toBe($publication2->id)
        ->and($response->json('data.1.id'))->toBe($publication3->id)
        ->and($response->json('data.2.id'))->toBe($publication1->id);
});

it('can sort publications by likes', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    [$publication1, $publication2] = Publication::factory(2)->permanent()->for($user)->create();

    [$channel1, $channel2] = SocialChannel::factory(2)->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();

    $channel1->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1->id,
        'key' => 'likes-count',
        'value' => 50,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'likes-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=likes&include=aggregate')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($publication1->id)
        ->and($response->json('data.1.id'))->toBe($publication2->id);
});

it('can sort publications by comments', function () {
    $this->seed(MetricsTableSeeder::class);
    $user = User::factory()->create();

    [$publication1, $publication2] = Publication::factory(2)->permanent()->for($user)->create();

    [$channel1, $channel2] = SocialChannel::factory(2)->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();

    $channel1->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1->id,
        'key' => 'comments-count',
        'value' => 25,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'comments-count',
        'value' => 75,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=-comments&include=aggregate')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($publication2->id)
        ->and($response->json('data.1.id'))->toBe($publication1->id);
});

it('handles publications with no metrics when sorting', function () {
    $this->seed(MetricsTableSeeder::class);
    $user = User::factory()->create();

    [$publication1, $publication2, $publication3] = Publication::factory(3)->permanent()->for($user)->create();

    [$channel1, $channel2] = SocialChannel::factory(2)->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();

    $channel1->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'views-count',
        'value' => 200,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    // Test ascending sort (publication3 with no metrics should be first)
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=views&include=aggregate')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($publication3->id)
        ->and($response->json('data.1.id'))->toBe($publication1->id)
        ->and($response->json('data.2.id'))->toBe($publication2->id);

    // Test descending sort (publication3 with no metrics should be last)
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=-views&include=aggregate')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($publication2->id)
        ->and($response->json('data.1.id'))->toBe($publication1->id)
        ->and($response->json('data.2.id'))->toBe($publication3->id);
});

it('can sort publications with multiple channels by total metrics', function () {
    $this->seed(MetricsTableSeeder::class);
    $user = User::factory()->create();

    [$publication1, $publication2] = Publication::factory(2)->permanent()->for($user)->create();

    $channel1a = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();
    $channel1b = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->youtube(), 'account')->create();
    $channel2 = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->linkedin(), 'account')->create();

    $channel1a->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel1b->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    $channel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1a->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $channel1b->id,
        'key' => 'views-count',
        'value' => 150,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $channel2->id,
        'key' => 'views-count',
        'value' => 200,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?sort=-views&include=aggregate')
        ->assertOk();

    // Publication 1 (250=150+100 total) should be first, then Publication 2 (200)
    expect($response->json('data.0.id'))->toBe($publication1->id)
        ->and($response->json('data.1.id'))->toBe($publication2->id);
});

it('combines metrics from multiple channels when including metrics', function () {
    $this->seed(MetricsTableSeeder::class);

    $user = User::factory()->create();

    $publication = Publication::factory()->permanent()->for($user)->create();

    [$channel1, $channel2] = SocialChannel::factory(2)->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();

    $channel1->publications()->attach($publication, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $channel2->publications()->attach($publication, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel1->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel1->id,
        'key' => 'likes-count',
        'value' => 50,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel1->id,
        'key' => 'comments-count',
        'value' => 25,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel2->id,
        'key' => 'views-count',
        'value' => 200,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel2->id,
        'key' => 'likes-count',
        'value' => 75,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication->id,
        'social_channel_id' => $channel2->id,
        'key' => 'comments-count',
        'value' => 40,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?include=aggregate')
        ->assertOk();

    // Total metrics should be: 300 views, 125 likes, 65 comments
    expect($response->json('data.0.aggregate'))
        ->toHaveKey('views-count', 300)
        ->toHaveKey('likes-count', 125)
        ->toHaveKey('comments-count', 65);
});

it('can filter and sort publications simultaneously', function () {
    $this->seed(MetricsTableSeeder::class);
    $user = User::factory()->create();

    [$publication1, $publication2, $publication3] = Publication::factory(3)->permanent()->for($user)->create();

    $tiktokChannel1 = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();
    $tiktokChannel2 = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->tiktok(), 'account')->create();
    $youtubeChannel = SocialChannel::factory()->for(SocialAccount::factory()->for($user)->youtube(), 'account')->create();

    $tiktokChannel1->publications()->attach($publication1, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $tiktokChannel2->publications()->attach($publication2, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);
    $youtubeChannel->publications()->attach($publication3, ['status' => SocialUploadStatus::COMPLETED->value, 'post_type' => PostType::POST->value]);

    PublicationAggregate::create([
        'publication_id' => $publication1->id,
        'social_channel_id' => $tiktokChannel1->id,
        'key' => 'views-count',
        'value' => 100,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication2->id,
        'social_channel_id' => $tiktokChannel2->id,
        'key' => 'views-count',
        'value' => 300,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    PublicationAggregate::create([
        'publication_id' => $publication3->id,
        'social_channel_id' => $youtubeChannel->id,
        'key' => 'views-count',
        'value' => 200,
        'type' => AggregateType::TOTAL->value,
        'last_updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?filter[channel]=tiktok&sort=-views&include=aggregate')
        ->assertOk();

    // Should only include TikTok publications, sorted by views
    expect($response->json('data'))
        ->toHaveCount(2)
        ->and($response->json('data.0.id'))->toBe($publication2->id) // 300 views
        ->and($response->json('data.1.id'))->toBe($publication1->id); // 100 views
});

it('can filter publications by video title', function () {
    $user = User::factory()->create();

    [$video1, $video2, $video3] = Video::factory()->completed()->for($user)->forEachSequence(
        ['title' => 'Saladin and the Crusades'],
        ['title' => 'Battle of Hattin'],
        ['title' => 'Siege of Jerusalem']
    )->create();

    [$pub1, $pub2, $pub3] = Publication::factory()->for($user)->permanent()->forEachSequence(
        ['video_id' => $video1->id],
        ['video_id' => $video2->id],
        ['video_id' => $video3->id]
    )->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/publications?filter[search]=siege')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $pub3->id]);
});
