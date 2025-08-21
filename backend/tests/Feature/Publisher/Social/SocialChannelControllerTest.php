<?php

namespace Tests\Feature\Publisher\Social;

use Queue;
use Mockery;
use Carbon\Carbon;
use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Middleware\PaidCustomersMiddleware;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Channels\Vendors\Business\LinkedInProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Queue::fake();
});

it('can receive a callback and send organizations list for LinkedIn account', function () {
    Carbon::setTestNow('2023-01-01 00:00:00');

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'paging' => [
                'count' => 10,
                'start' => 0,
                'links' => [],
            ],
            'elements' => [
                [
                    'role' => 'ADMINISTRATOR',
                    'organization~' => [
                        'vanityName' => 'testing-company-312',
                        'localizedName' => 'Testing Company',
                        'id' => '696969',
                        'localizedWebsite' => 'https://www.testing-company.com',
                    ],
                    'organization' => 'urn:li:organization:100697797',
                    'roleAssignee' => 'urn:li:person:n98UUIna',
                    'state' => 'APPROVED',
                ],
                [
                    'role' => 'ADMINISTRATOR',
                    'organization~' => [
                        'vanityName' => 'another-company-312',
                        'localizedName' => 'Another Company',
                        'id' => '676767',
                        'localizedWebsite' => 'https://www.another-company.com',
                    ],
                    'organization' => 'urn:li:organization:676767',
                    'roleAssignee' => 'urn:li:person:n98UUIna',
                    'state' => 'APPROVED',
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser->token = Str::random(40);
    $abstractUser->refreshToken = Str::random(40);

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn($avatar = 'https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name)
        ->shouldReceive('getRaw')->andReturn(['refresh_token_expires_in' => 5184000]);

    Socialite::shouldReceive('driver->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/linkedin/channels?code=123456&type=organization')
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'provider_id' => '123456789',
                    'organization_id' => '696969',
                    'organization_name' => 'Testing Company',
                    'role' => 'ADMINISTRATOR',
                ],
                [
                    'provider_id' => '123456789',
                    'organization_id' => '676767',
                    'organization_name' => 'Another Company',
                    'role' => 'ADMINISTRATOR',
                ],
            ],
        ]);

    $this->assertTrue(Cache::has(LinkedInProvider::CACHE_KEY.'123456789'));
});

it('can receive a callback with no organizations as well', function () {
    Carbon::setTestNow('2023-01-01 00:00:00');

    Http::fake([
        'https://api.linkedin.com/v2/*' => Http::response([
            'paging' => [
                'count' => 10,
                'start' => 0,
                'links' => [],
            ],
            'elements' => [],
        ]),
    ]);

    $user = User::factory()->create();

    $newUser = User::factory()->make();

    $abstractUser = Mockery::mock(SocialiteUser::class);

    $abstractUser->token = Str::random(40);
    $abstractUser->refreshToken = Str::random(40);

    $abstractUser
        ->shouldReceive('getId')->andReturn('123456789')
        ->shouldReceive('getEmail')->andReturn($newUser->email)
        ->shouldReceive('getNickname')->andReturn(Str::limit($newUser->name, 6))
        ->shouldReceive('getAvatar')->andReturn($avatar = 'https://example.com/avatar.jpg')
        ->shouldReceive('getName')->andReturn($newUser->name)
        ->shouldReceive('getRaw')->andReturn(['refresh_token_expires_in' => 5184000]);

    Socialite::shouldReceive('driver->stateless->user')->andReturn($abstractUser);

    $this->actingAs($user, 'sanctum')
        ->getJson('v1/social/callback/linkedin/channels?code=123456&type=organization')
        ->assertOk()
        ->assertJson(['data' => []]);

    $this->assertTrue(Cache::has(LinkedInProvider::CACHE_KEY.'123456789'));
});

it('can save a linkedin organization', function () {
    Carbon::setTestNow('2023-01-01 00:00:00');

    Http::fake([
        'https://api.linkedin.com/v2/organizations/*' => Http::response([
            'logoV2' => [
                'original~' => [
                    'elements' => [
                        [
                            'identifiers' => [
                                [
                                    'identifier' => 'https://example.com/logo.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $data = [
        'provider' => SocialAccountEnum::LinkedIn->value,
        'provider_id' => $id = '123456789',
        'name' => 'John Doe',
        'avatar' => 'https://example.com/avatar.jpg',
        'email' => null,
        'access_token' => $token = Str::random(40),
        'expires_in' => 5184000,
        'refresh_token' => $refreshToken = Str::random(40),
        'refresh_expires_in' => 5184000,
        'needs_reauth' => false,
    ];

    Cache::put(LinkedInProvider::CACHE_KEY.$id, $data, 600);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('v1/social/callback/linkedin/channels', [
        'provider_id' => $id,
        'channels' => [
            [
                'id' => '696969',
                'type' => 'organization',
                'name' => 'Testing Company',
            ],
            [
                'id' => '676767',
                'type' => 'organization',
                'name' => 'Another Company',
            ],
        ],
    ])->assertCreated();

    $this->assertDatabaseHas(SocialChannel::class, [
        'social_account_id' => $user->socialAccounts()->first()->id,
        'provider_id' => '696969',
        'type' => SocialChannel::ORGANIZATION,
        'name' => 'Testing Company',
    ]);

    $this->assertDatabaseHas(SocialChannel::class, [
        'social_account_id' => $user->socialAccounts()->first()->id,
        'provider_id' => '676767',
        'type' => SocialChannel::ORGANIZATION,
        'name' => 'Another Company',
    ]);

    $this->assertDatabaseHas(SocialAccount::class, [
        'user_id' => $user->id,
        'provider' => SocialAccountEnum::LinkedIn,
        'provider_id' => '123456789',
        'access_token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 5184000,
        'refresh_expires_in' => 5184000,
        'needs_reauth' => false,
    ]);
});
