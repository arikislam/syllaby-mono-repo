<?php

namespace Tests\Feature\Publisher\Social;

use Http;
use Event;
use Queue;
use Mockery;
use Carbon\Carbon;
use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Media;
use Google\Service\YouTube\Channel;
use Laravel\Socialite\Facades\Socialite;
use Google\Service\YouTube\ChannelListResponse;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Jobs\ExtractFacebookProfilePic;
use App\Syllaby\Publisher\Channels\Services\Youtube\ChannelService;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake(MediaHasBeenAddedEvent::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    $this->mock(TransloadMediaAction::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->andReturn(Media::factory()->create());
    });
});

it('can receive a youtube account callback and create an account', function () {
    $this->partialMock(ChannelService::class, function (MockInterface $mock) {
        $mock->shouldReceive('exists')->once()->andReturn(true);
        $mock->shouldReceive('getChannelsList')->andReturn(
            new ChannelListResponse([
                'items' => [
                    new Channel([
                        'id' => '123456789',
                        'snippet' => [
                            'title' => 'Test Channel',
                            'thumbnails' => [
                                'default' => ['url' => 'https://via.placeholder.com/150'],
                            ],
                        ],
                    ]),
                ],
            ])
        );
    });

    $user = User::factory()->create();

    /** @var User $newUser */
    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->refreshToken = $refreshToken = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name);

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/youtube?code=123456&redirect_url=https://ai.syllaby.io/social-connect/youtube')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Youtube')
        ->needs_re_auth->toBe(false)
        ->and($response->json('data.channels.0'))
        ->provider_id->toBe('123456789')
        ->name->toBe('Test Channel')
        ->type->toBe(SocialChannel::INDIVIDUAL);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Youtube,
        'provider_id' => '123456789',
        'access_token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 3600,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => '123456789',
        'name' => 'Test Channel',
        'type' => SocialChannel::INDIVIDUAL,
    ]);
});

it('throws error if youtube channel doesnt exists', function () {
    $this->partialMock(ChannelService::class, function (MockInterface $mock) {
        $mock->shouldReceive('exists')->once()->andReturn(false);
    });

    $user = User::factory()->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = Str::random(40);
    $abstractUser->shouldReceive('getId')->andReturn('123456789');

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/youtube?code=123456&redirect_url=https://ai.syllaby.io/social-connect/youtube')
        ->assertInternalServerError()
        ->assertJsonPath('message', 'We are unable to find channel associated with this account. Please try again. If the problem persists, contact support.'); // Extract to lang config file in facebook-branch
});

it('can receive a tiktok account callback and create an account', function () {
    Carbon::setTestNow('2021-01-01 00:00:00');

    /** @var User $user */
    $user = User::factory()->create();

    /** @var User $newUser */
    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser->token = $token = Str::random(40);
    $abstractUser->refreshToken = $refreshToken = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn($avatar = 'https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name);

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/tiktok?code=123456&redirect_url=https://ai.syllaby.io/social-connect/tiktok')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('TikTok')
        ->needs_re_auth->toBe(false)
        ->and($response->json('data.channels.0'))
        ->provider_id->toBe('123456789')
        ->name->toBe($newUser->name)
        ->type->toBe(SocialChannel::INDIVIDUAL);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::TikTok,
        'provider_id' => '123456789',
        'access_token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 3600,
        'refresh_expires_in' => floor(now()->diffInSeconds(now()->addYear())),
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => '123456789',
        'name' => $newUser->name,
        'type' => SocialChannel::INDIVIDUAL,
    ]);
});

it('will update the details if same social channel is connected twice', function () {
    $user = User::factory()->create();
    $account = SocialAccount::factory()->for($user)->youtube()->create();

    $this->partialMock(ChannelService::class, function (MockInterface $mock) use ($account) {
        $mock->shouldReceive('exists')->once()->andReturn(true);
        $mock->shouldReceive('getChannelsList')->andReturn(
            new ChannelListResponse([
                'items' => [
                    new Channel([
                        'id' => $account->provider_id,
                        'snippet' => [
                            'title' => 'Sharryy',
                            'thumbnails' => [
                                'default' => ['url' => 'https://example.com/avatar.jpg'],
                            ],
                        ],
                    ]),
                ],
            ])
        );
    });

    SocialChannel::factory()->individual()->create(['social_account_id' => $account->id, 'provider_id' => $account->provider_id]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = Str::random(40);
    $abstractUser->refreshToken = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn($account->provider_id)
        ->shouldReceive('getAvatar')->andReturn($avatar = 'https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($name = 'Sharryy');

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/youtube?code=123456&redirect_url=https://ai.syllaby.io/social-connect/youtube')
        ->assertCreated();

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Youtube,
        'provider_id' => $account->provider_id,
        'access_token' => $abstractUser->token,
        'refresh_token' => $abstractUser->refreshToken,
        'expires_in' => $abstractUser->expiresIn,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseCount(SocialChannel::class, 1);
    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => $account->provider_id,
        'name' => $name,
        'type' => SocialChannel::INDIVIDUAL,
    ]);
});

it('can receive a callback for a LinkedIn Account', function () {
    Carbon::setTestNow(now());

    /** @var User $user */
    $user = User::factory()->create();

    /** @var User $newUser */
    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser->token = $token = Str::random(40);
    $abstractUser->refreshToken = $refreshToken = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn($avatar = 'https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name)
        ->shouldReceive('getRaw')->andReturn(['refresh_token_expires_in' => 5184000]);

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/linkedin?code=123456&redirect_url=https://ai.syllaby.io/social-connect/linkedin')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('LinkedIn')
        ->needs_re_auth->toBe(false)
        ->and($response->json('data.channels.0'))
        ->provider_id->toBe('123456789')
        ->name->toBe($newUser->name)
        ->type->toBe(SocialChannel::INDIVIDUAL);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::LinkedIn,
        'provider_id' => '123456789',
        'access_token' => $token,
        'refresh_token' => $refreshToken,
        'refresh_expires_in' => 5184000,
        'expires_in' => 3600,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => '123456789',
        'name' => $newUser->name,
        'type' => SocialChannel::INDIVIDUAL,
    ]);
});

it('can receive a callback for Facebook Page', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                ],
                [
                    'id' => 345,
                    'name' => 'Test-Channel 2',
                    'access_token' => 'another-long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser->token = $token = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name);

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/facebook?code=123456&redirect_url=https://ai.syllaby.io/social-connect/facebook')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Facebook')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(2);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Facebook,
        'provider_id' => '123456789',
        'access_token' => $token,
        'refresh_token' => null,
        'expires_in' => 3600,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 123,
        'name' => 'Test-Channel 1',
        'type' => SocialChannel::PAGE,
        'access_token' => 'long-random-token',
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 345,
        'name' => 'Test-Channel 2',
        'type' => SocialChannel::PAGE,
        'access_token' => 'another-long-random-token',
    ]);

    Queue::assertPushed(ExtractFacebookProfilePic::class);
});

it('updates the details of facebook page if it is already there', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Edited Test-Channel 1',
                    'access_token' => 'edited long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->facebook()->create();

    SocialChannel::factory()->for($account, 'account')->create(['provider_id' => 123, 'name' => 'Test-1', 'access_token' => 'token-1', 'type' => SocialChannel::PAGE]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->shouldReceive('getId')->andReturn($account->provider_id);
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/facebook?code=123456&redirect_url=https://ai.syllaby.io/social-connect/facebook')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Facebook')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(1);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Facebook,
        'provider_id' => $account->provider_id,
        'access_token' => $token,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseCount(SocialChannel::class, 1);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 123,
        'name' => 'Edited Test-Channel 1',
        'type' => SocialChannel::PAGE,
        'access_token' => 'edited long-random-token',
    ]);

    Queue::assertPushed(ExtractFacebookProfilePic::class);
});

it('rejects the non-admins for facebook page', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['ANALYSE'],
                ],
                [
                    'id' => 345,
                    'name' => 'Test-Channel 2',
                    'access_token' => 'another-long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->shouldReceive('getId')->andReturn('123456789');
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/facebook?code=123456&redirect_url=https://ai.syllaby.io/social-connect/facebook')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Facebook')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(1);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Facebook,
        'provider_id' => '123456789',
        'access_token' => $token,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseMissing(SocialChannel::class, [
        'provider_id' => 123,
        'name' => 'Test-Channel 1',
        'type' => SocialChannel::PAGE,
        'access_token' => 'long-random-token',
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 345,
        'name' => 'Test-Channel 2',
        'type' => SocialChannel::PAGE,
        'access_token' => 'another-long-random-token',
    ]);
});

it('throws error if channel is already connected with other account', function () {
    $user = User::factory()->create();

    $account = SocialAccount::factory()->facebook()->for(User::factory()->create())
        ->has(SocialChannel::factory()->page(), 'channels')
        ->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $account->channels->first()->access_token;
    $abstractUser->shouldReceive('getId')->andReturn($account->provider_id);
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/facebook?code=123456&redirect_url=https://ai.syllaby.io/social-connect/facebook')
        ->assertServerError()
        ->assertJsonFragment(['message' => 'This account is already connected to another user.']);
});

it('can receive a callback for instagram Page', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                    'instagram_business_account' => [
                        'id' => 56789,
                        'username' => 'test-username-1',
                        'profile_picture_url' => 'https://example.com/avatar.jpg',
                    ],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->expiresIn = 3600;
    $abstractUser->shouldReceive('getId')->andReturn('1234');

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/instagram?code=123456&redirect_url=https://ai.syllaby.io/social-connect/instagram')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Instagram')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(1);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Instagram,
        'provider_id' => '1234',
        'access_token' => $token,
        'refresh_token' => null,
        'expires_in' => 3600,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 56789,
        'name' => 'test-username-1',
        'type' => SocialChannel::PROFESSIONAL_ACCOUNT,
        'access_token' => 'long-random-token',
    ]);
});

it('only add channels connected to instagram and ignore others', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                    'instagram_business_account' => [
                        'id' => 56789,
                        'username' => 'test-username-1',
                        'profile_picture_url' => 'https://example.com/avatar.jpg',
                    ],
                ],
                [
                    'id' => 345,
                    'name' => 'Test-Channel 2',
                    'access_token' => 'another-long-random-token',
                    'tasks' => [],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->expiresIn = 3600;
    $abstractUser->shouldReceive('getId')->andReturn('1234');

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/instagram?code=123456&redirect_url=https://ai.syllaby.io/social-connect/instagram')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Instagram')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(1);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Instagram,
        'provider_id' => '1234',
        'access_token' => $token,
        'refresh_token' => null,
        'expires_in' => 3600,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseCount(SocialChannel::class, 1);
    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 56789,
        'name' => 'test-username-1',
        'type' => SocialChannel::PROFESSIONAL_ACCOUNT,
        'access_token' => 'long-random-token',
    ]);
});

it('updates the details of instagram page if it is already there', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['CREATE_CONTENT', 'MANAGE', 'MODERATE'],
                    'instagram_business_account' => [
                        'id' => 56789,
                        'username' => 'edited test-username-1',
                        'profile_picture_url' => 'https://example.com/avatar.jpg',
                    ],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $account = SocialAccount::factory()->for($user)->instagram()->create();

    SocialChannel::factory()->for($account, 'account')->create(['provider_id' => 56789, 'name' => 'Test-1', 'access_token' => 'token-1', 'type' => SocialChannel::PROFESSIONAL_ACCOUNT]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->expiresIn = 86400;
    $abstractUser->shouldReceive('getId')->andReturn($account->provider_id);
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/instagram?code=123456&redirect_url=https://ai.syllaby.io/social-connect/instagram')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Instagram')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(1);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Instagram,
        'provider_id' => $account->provider_id,
        'access_token' => $token,
        'expires_in' => 86400,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseCount(SocialChannel::class, 1);
    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => 56789,
        'name' => 'edited test-username-1',
        'type' => SocialChannel::PROFESSIONAL_ACCOUNT,
        'access_token' => 'long-random-token',
    ]);
});

it('rejects the non-admins for instagram page', function () {
    Queue::fake();

    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 123,
                    'name' => 'Test-Channel 1',
                    'access_token' => 'long-random-token',
                    'tasks' => ['ANALYSE'],
                    'instagram_business_account' => [
                        'id' => 56789,
                        'username' => 'edited test-username-1',
                        'profile_picture_url' => 'https://example.com/avatar.jpg',
                    ],
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'dummy-value',
                    'after' => 'dummy-value',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $token = Str::random(40);
    $abstractUser->shouldReceive('getId')->andReturn('123456789');
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/instagram?code=123456&redirect_url=https://ai.syllaby.io/social-connect/instagram')
        ->assertCreated();

    expect($response->json('data'))
        ->provider->toBe('Instagram')
        ->needs_re_auth->toBe(false)
        ->channels->toHaveCount(0);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Instagram,
        'provider_id' => '123456789',
        'access_token' => $token,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseCount(SocialChannel::class, 0);
});

it('throws error if instagram channel is already connected with other account', function () {
    $user = User::factory()->create();

    $account = SocialAccount::factory()->instagram()->for(User::factory()->create())
        ->has(SocialChannel::factory()->page(), 'channels')
        ->create();

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = $account->channels->first()->access_token;
    $abstractUser->shouldReceive('getId')->andReturn($account->provider_id);
    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/instagram?code=123456&redirect_url=https://ai.syllaby.io/social-connect/instagram')
        ->assertServerError()
        ->assertJsonFragment(['message' => 'This account is already connected to another user.']);
});

it('can receive a callback for threads account', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://graph.threads.net/*' => Http::response([
            'access_token' => $longLivedToken = 'long-random-token',
            'expires_in' => $newExpiresIn = 86400,
        ]),
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->token = Str::random(40);
    $abstractUser->expiresIn = 3600;

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($name = 'Sharryy')
        ->shouldReceive('setToken')->andReturnUsing(fn () => tap($abstractUser, fn ($user) => $user->token = $longLivedToken))
        ->shouldReceive('setExpiresIn')->andReturnUsing(fn () => tap($abstractUser, fn ($user) => $user->expiresIn = $newExpiresIn));

    Socialite::shouldReceive('driver->redirectUrl->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/threads?code=123456&redirect_url=https://ai.syllaby.io/social-connect/threads')
        ->assertCreated();

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::Threads,
        'provider_id' => '123456789',
        'access_token' => $longLivedToken,
        'refresh_token' => null,
        'expires_in' => $newExpiresIn,
        'needs_reauth' => 0,
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'provider_id' => '123456789',
        'name' => $name,
        'access_token' => null,
        'type' => SocialChannel::INDIVIDUAL,
    ]);
});
