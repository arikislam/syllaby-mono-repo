<?php

namespace Tests\Feature\Publisher\Publication\TikTok;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Notification;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Http\Middleware\TikTokSignatureValidator;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\DTOs\TikTokVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Enums\TikTokPrivacyStatus;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware([PaidCustomersMiddleware::class, TikTokSignatureValidator::class]);
});

it('handles video published events', function () {
    Notification::fake();

    $user = User::factory()->create();

    $metadata = TikTokVideoData::fromArray([
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'publish_id' => 'v_pub_url~v2-1.7270866307037169669',
    ]);

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user), 'account')
        ->hasAttached(Publication::factory()->permanent()->for($user), [
            'status' => SocialUploadStatus::PROCESSING->value,
            'metadata' => $metadata->toArray(),
        ])->create();

    $this->postJson('tiktok/webhook', [
        'event' => 'post.publish.complete',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'publish_id' => $metadata->publish_id,
            'publish_type' => 'DIRECT_PUBLISH',
        ]),
    ])->assertOk();

    $tiktok->refresh();

    expect($tiktok->publications->first()->pivot)->status->toBe(SocialUploadStatus::COMPLETED->value);

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('handles authorization removed events', function () {
    $user = User::factory()->create();

    $tiktok = SocialChannel::factory()->individual()->for(
        SocialAccount::factory()->tiktok()->for($user), 'account'
    )->create();

    $this->postJson('tiktok/webhook', [
        'event' => 'authorization.removed',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'reason' => 4,
        ]),
    ])->assertOk();

    $tiktok->refresh();

    expect($tiktok->account)
        ->needs_reauth->toBe(true)
        ->errors->toBeArray()
        ->errors->tiktok->message->toBe('User\'s account got banned');
});

it('can set the video id of the published tiktok video', function () {
    Notification::fake();

    $user = User::factory()->create();

    $metadata = TikTokVideoData::fromArray([
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'publish_id' => 'v_pub_url~v2-1.7270866307037169669',
    ]);

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user), 'account')
        ->hasAttached(Publication::factory()->permanent()->for($user), [
            'status' => SocialUploadStatus::PROCESSING->value,
            'metadata' => $metadata->toArray(),
        ])->create();

    $this->postJson('tiktok/webhook', [
        'event' => 'post.publish.complete',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'publish_id' => $metadata->publish_id,
            'publish_type' => 'DIRECT_PUBLISH',
        ]),
    ])->assertOk();

    $this->postJson('tiktok/webhook', [
        'event' => 'post.publish.publicly_available',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'publish_id' => $metadata->publish_id,
            'publish_type' => 'DIRECT_PUBLISH',
            'post_id' => '7270866307037169669',
        ]),
    ])->assertOk();

    $tiktok->refresh();

    expect($tiktok->publications->first()->pivot)
        ->provider_media_id->toBe('7270866307037169669')
        ->status->toBe(SocialUploadStatus::COMPLETED->value);

    Notification::assertSentTo($user, PublicationSuccessful::class);
});

it('handles post failed events', function () {
    $user = User::factory()->create();

    $metadata = TikTokVideoData::fromArray([
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'publish_id' => 'v_pub_url~v2-1.7270866307037169669',
    ]);

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user), 'account')
        ->hasAttached(Publication::factory()->permanent()->for($user), [
            'status' => SocialUploadStatus::PROCESSING->value,
            'metadata' => $metadata->toArray(),
        ])->create();

    $this->postJson('tiktok/webhook', [
        'event' => 'post.publish.failed',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'publish_id' => $metadata->publish_id,
            'publish_type' => 'DIRECT_PUBLISH',
            'reason' => 'duration_check',
        ]),
    ])->assertOk();

    $tiktok->refresh();

    expect($tiktok->publications()->first()->pivot)
        ->status->toBe(SocialUploadStatus::FAILED->value)
        ->error_message->toBe('duration_check');
});

it('handles post removed publicily event', function () {
    $user = User::factory()->create();

    $metadata = TikTokVideoData::fromArray([
        'caption' => 'This is a test caption for tiktok video',
        'allow_comments' => true,
        'allow_duet' => false,
        'allow_stitch' => true,
        'privacy_status' => TikTokPrivacyStatus::SELF_ONLY->name,
        'publish_id' => 'v_pub_url~v2-1.7270866307037169669',
    ]);

    $tiktok = SocialChannel::factory()->individual()
        ->for(SocialAccount::factory()->tiktok()->for($user), 'account')
        ->hasAttached(Publication::factory()->permanent()->for($user), [
            'status' => SocialUploadStatus::PROCESSING->value,
            'metadata' => $metadata->toArray(),
            'provider_media_id' => 'some-dummmy-id',
        ])->create();

    $this->postJson('tiktok/webhook', [
        'event' => 'post.publish.no_longer_publicly_available',
        'user_openid' => $tiktok->provider_id,
        'content' => json_encode([
            'publish_id' => $metadata->publish_id,
            'publish_type' => 'DIRECT_PUBLISH',
            'post_id' => 'some-dummmy-id',
        ]),
    ])->assertOk();

    $tiktok->refresh();

    expect($tiktok->publications->first()->pivot)
        ->provider_media_id->toBeNull()
        ->status->toBe(SocialUploadStatus::REMOVED_BY_USER->value);
});
