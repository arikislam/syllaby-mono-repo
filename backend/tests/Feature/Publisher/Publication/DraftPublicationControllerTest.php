<?php

namespace Tests\Feature\Publisher\Publication;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Facades\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can create a draft post', function () {
    $user = User::factory()->create();

    $youtube = SocialChannel::factory()->for(
        SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account'
    )->create();
    $tiktok = SocialChannel::factory()->for(
        SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account'
    )->create();
    $linkedin = SocialChannel::factory()->individual()->for(
        SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account'
    )->create();
    $facebook = SocialChannel::factory()->page()->for(
        SocialAccount::factory()->facebook()->for($user)->createQuietly(), 'account'
    )->create();
    $instagram = SocialChannel::factory()->professional()->for(
        SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account'
    )->create();
    $thread = SocialChannel::factory()->for(
        SocialAccount::factory()->threads()->for($user)->createQuietly(), 'account'
    )->create();

    $publication = Publication::factory()->for($user)->createQuietly();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/draft', [
        'publication_id' => $publication->id,
        'youtube' => [
            [
                'channel_id' => $youtube->id,
                'title' => 'Test Title',
                'description' => 'Test description for the video',
                'privacy_status' => 'public',
                'category' => 22,
                'tags' => ['tag1', 'tag2', 'tag3'],
                'license' => 'creativeCommon',
                'embeddable' => true,
                'notify_subscribers' => true,
                'made_for_kids' => false,
            ],
        ],
        'tiktok' => [
            [
                'channel_id' => $tiktok->id,
                'caption' => 'This is a test caption for tiktok video',
                'allow_comments' => true,
                'allow_duet' => false,
                'allow_stitch' => true,
                'privacy_status' => 'SELF_ONLY',
            ],
        ],
        'linkedin' => [
            [
                'channel_id' => $linkedin->id,
                'title' => 'Test Title',
                'caption' => 'Test caption',
            ],
        ],
        'facebook' => [
            [
                'channel_id' => $facebook->id,
                'post_type' => 'reel',
                'caption' => 'Test Facebook Caption',
            ],
        ],
        'instagram' => [
            [
                'channel_id' => $instagram->id,
                'post_type' => 'reel',
                'caption' => 'Test Instagram Caption',
                'share_to_feed' => true,
            ],
        ],
        'threads' => [
            [
                'channel_id' => $thread->id,
                'caption' => 'Test threads Caption',
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $youtube->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'post',
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $tiktok->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'post',
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedin->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'post',
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $facebook->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'reel',
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $instagram->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'reel',
    ]);

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $thread->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
        'post_type' => 'post',
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => true,
        'temporary' => false,
        'scheduled' => false,
    ]);

    $this->assertDatabaseCount('events', 0);
});

it('cant create a draft post for invalid post-type', function () {
    $user = User::factory()->create();

    $facebook = SocialChannel::factory()->page()->for(
        SocialAccount::factory()->facebook()->for($user)->createQuietly(), 'account'
    )->create();
    $instagram = SocialChannel::factory()->professional()->for(
        SocialAccount::factory()->instagram()->for($user)->createQuietly(), 'account'
    )->create();

    $publication = Publication::factory()->for($user)->createQuietly();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/draft', [
        'publication_id' => $publication->id,
        'facebook' => [
            [
                'channel_id' => $facebook->id,
                'post_type' => 'short',
                'caption' => 'Test Facebook Caption',
            ],
        ],
        'instagram' => [
            [
                'channel_id' => $instagram->id,
                'post_type' => 'post',
                'caption' => 'Test Instagram Caption',
                'share_to_feed' => true,
            ],
        ],
    ])->assertUnprocessable();
});

it('can create a draft scheduled post', function () {
    Carbon::setTestNow('2021-01-01 00:00:00');

    $user = User::factory()->create();

    $youtube = SocialChannel::factory()->for(
        SocialAccount::factory()->youtube()->for($user)->createQuietly(), 'account'
    )->create();

    $publication = Publication::factory()->for($user)->createQuietly();

    $this->actingAs($user, 'sanctum')->postJson('v1/publish/draft', [
        'publication_id' => $publication->id,
        'scheduled_at' => now()->addHour(),
        'youtube' => [
            [
                'channel_id' => $youtube->id,
                'title' => 'Test Title',
                'description' => 'Test description for the video',
                'privacy_status' => 'public',
                'category' => 22,
                'tags' => ['tag1', 'tag2', 'tag3'],
                'license' => 'creativeCommon',
                'embeddable' => true,
                'notify_subscribers' => true,
                'made_for_kids' => false,
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $youtube->id,
        'publication_id' => $publication->id,
        'status' => SocialUploadStatus::DRAFT->value,
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => true,
        'temporary' => false,
        'scheduled' => true,
    ]);

    $this->assertDatabaseCount('events', 0);
});

it('can create a draft post for linkedin organization', function () {
    $user = User::factory()->create();

    $linkedin = SocialChannel::factory()->organization()
        ->for(SocialAccount::factory()->linkedin()->for($user)->createQuietly(), 'account')
        ->create();

    $publication = Publication::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->postJson('v1/publish/draft', [
        'publication_id' => $publication->id,
        'linkedin' => [
            [
                'channel_id' => $linkedin->id,
                'visibility' => 'CONNECTIONS',
                'title' => 'Test Title',
                'caption' => 'Test caption',
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('account_publications', [
        'social_channel_id' => $linkedin->id,
        'publication_id' => $publication->id,
    ]);

    $this->assertDatabaseHas('publications', [
        'id' => $publication->id,
        'draft' => true,
        'temporary' => false,
    ]);

    expect($response->json('data.accounts.0.metadata'))
        ->title->toBe('Test Title')
        ->caption->toBe('Test caption');
});
