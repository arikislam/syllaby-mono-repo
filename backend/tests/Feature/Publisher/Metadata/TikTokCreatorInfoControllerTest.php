<?php

namespace Tests\Feature\Publisher\Metadata;

use GuzzleHttp\Client;
use App\Syllaby\Users\User;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can fetch creator-info for a tiktok account', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'creator_nickname' => 'sharryy_0',
                'creator_username' => 'sharryy_0',
                'duet_disabled' => true,
                'max_video_post_duration_sec' => 600,
                'privacy_level_options' => [
                    'FOLLOWER_OF_CREATOR',
                    'MUTUAL_FOLLOW_FRIENDS',
                    'SELF_ONLY',
                ],
                'stitch_disabled' => true,
                'comment_disabled' => false,
                'creator_avatar_url' => 'https://www.github.com/sharryy.png',
            ],
            'error' => [
                'code' => 'ok',
                'message' => '',
                'log_id' => '202308292041259B7941E117EC8113133A',
            ],
        ])),
    ]);

    $stack = HandlerStack::create($mock);

    $client = new Client(['handler' => $stack]);

    $this->app->instance(Client::class, $client);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->for(
        SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account'
    )->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('v1/metadata/tiktok/creator-info', [
        'id' => $channel->id,
    ])->assertOk();

    expect($response->json('data'))
        ->toBeArray()
        ->toHaveKeys([
            'creator_avatar_url', 'creator_username', 'creator_nickname', 'privacy_level_options',
            'comment_disabled', 'duet_disabled', 'stitch_disabled', 'max_video_post_duration_sec',
        ]);
});

it('handles correctly in case of error from API', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [],
            'error' => [
                'code' => 'INVALID_PARAMETER',
                'message' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'log_id' => '202308292041259B7941E117EC8113133A',
            ],
        ])),
    ]);

    $stack = HandlerStack::create($mock);

    $client = new Client(['handler' => $stack]);

    $this->app->instance(Client::class, $client);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->for(
        SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account'
    )->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/metadata/tiktok/creator-info', [
        'id' => $channel->id,
    ])->assertServerError();
});

it('caches the creator-info for a tiktok account', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                'creator_nickname' => 'sharryy_0',
                'creator_username' => 'sharryy_0',
                'duet_disabled' => true,
                'max_video_post_duration_sec' => 600,
                'privacy_level_options' => [
                    'FOLLOWER_OF_CREATOR',
                    'MUTUAL_FOLLOW_FRIENDS',
                    'SELF_ONLY',
                ],
                'stitch_disabled' => true,
                'comment_disabled' => false,
                'creator_avatar_url' => 'https://www.github.com/sharryy.png',
            ],
            'error' => [
                'code' => 'ok',
                'message' => '',
                'log_id' => '202308292041259B7941E117EC8113133A',
            ],
        ])),
    ]);

    $stack = HandlerStack::create($mock);

    $client = new Client(['handler' => $stack]);

    $this->app->instance(Client::class, $client);

    $user = User::factory()->create();

    $channel = SocialChannel::factory()->for(
        SocialAccount::factory()->tiktok()->for($user)->createQuietly(), 'account'
    )->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/metadata/tiktok/creator-info', [
        'id' => $channel->id,
    ])->assertOk();

    expect(Cache::get('tiktok.creator_info:'.$channel->id))->not->toBeNull();
});
