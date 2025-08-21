<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Queue;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Animation\Jobs\CreateAnimationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('unauthenticated users cannot access animation endpoints', function () {
    $faceless = Faceless::factory()->create();
    $asset = Asset::factory()->create();

    $this->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'animations' => [
            [
                'id' => 99999,
                'index' => 0,
            ],
        ],
    ])->assertUnauthorized();
});

test('users cannot animate videos they do not own', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->create();

    $asset = Asset::factory()->create();

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'id' => $asset->id,
        'index' => 0,
    ])->assertForbidden();
});

test('users cannot animate without required parameters', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [])
        ->assertUnprocessable();

    expect($response->json('errors'))->toHaveKeys(['animations']);
});

test('users cannot animate with non-existent asset id', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
            'animations' => [
                [
                    'id' => 99999,
                    'index' => 0,
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('animations');
});

test('users cannot animate non-image assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->for($user)->withMedia(mime: 'audio/mpeg')->create(['type' => AssetType::AUDIOS]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'animations' => [
            [
                'id' => $asset->id,
                'index' => 0,
            ],
        ],
    ])->assertJsonValidationErrors('animations')
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'One or more assets are invalid or do not exist.']);
});

test('users can successfully initiate single animation', function () {
    Queue::fake();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);
    $asset->videos()->attach($faceless->id, ['order' => 0]);

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'animations' => [
            [
                'id' => $asset->id,
                'index' => 0,
                'prompt' => 'Make it lively',
            ],
        ],
    ])->assertOk();

    expect($response->json('0'))->toHaveKeys(['id', 'type', 'status'])
        ->and($response->json('0.status'))->toBe(AssetStatus::PROCESSING->value)
        ->and($response->json('0.type'))->toBe(AssetType::AI_VIDEO->value);

    $this->assertDatabaseHas('assets', [
        'user_id' => $user->id,
        'parent_id' => $asset->id,
        'type' => AssetType::AI_VIDEO->value,
        'provider' => AssetProvider::MINIMAX->value,
        'status' => AssetStatus::PROCESSING->value,
        'genre_id' => $faceless->genre_id,
        'description' => 'Make it lively',
        'is_private' => true,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_id' => $faceless->id,
        'model_type' => 'faceless',
        'asset_id' => $asset->id,
        'order' => 0,
    ]);

    $this->assertDatabaseHas('video_assets', [
        'model_id' => $faceless->id,
        'model_type' => 'faceless',
        'asset_id' => $response->json('0.id'),
        'order' => 0,
    ]);

    Queue::assertPushed(CreateAnimationJob::class, function ($job) use ($asset) {
        return $job->faceless->id === $asset->videos->first()->id && $job->prompt === 'Make it lively';
    });
});

test('users can successfully initiate multiple animations', function () {
    Queue::fake();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $assets = Asset::factory()
        ->for($user)
        ->withMedia()
        ->forEachSequence(
            ['type' => AssetType::CUSTOM_IMAGE],
            ['type' => AssetType::CUSTOM_IMAGE],
        )
        ->create();

    $assets->each(fn ($asset, $index) => $asset->videos()->attach($faceless->id, ['order' => $index]));

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'animations' => [
            [
                'id' => $assets[0]->id,
                'index' => 0,
                'prompt' => 'Make it lively',
            ],
            [
                'id' => $assets[1]->id,
                'index' => 1,
                'prompt' => 'Add some motion',
            ],
        ],
    ])->assertOk();

    expect($response->json())->toBeArray()->toHaveCount(2);

    foreach ($response->json() as $asset) {
        expect($asset)->toHaveKeys(['id', 'type', 'status'])
            ->and($asset['status'])->toBe(AssetStatus::PROCESSING->value)
            ->and($asset['type'])->toBe(AssetType::AI_VIDEO->value);
    }

    Queue::assertPushed(CreateAnimationJob::class, 2);
});

test('users can fetch animation status', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->for($user)->processing()->aiVideo()->create();
    $asset->videos()->attach($faceless->id, ['order' => 0]);

    $response = $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/media/animation?id={$asset->id}")
        ->assertOk();

    expect($response->json('data'))->toHaveKeys(['id', 'type', 'status', 'order']);
});

test('users cannot fetch animation status without id', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/media/animation")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);
});

test('users cannot fetch non-existent animation', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson("/v1/videos/faceless/{$faceless->id}/media/animation?id=999999")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);
});

test('credits are deducted while generating animations', function () {
    Queue::fake();

    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 500,
    ]);

    $faceless = Faceless::factory()->ai()->for($user)->create();

    $asset = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);

    $asset->videos()->attach($faceless->id, ['order' => 0]);

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/media/animation", [
        'animations' => [
            [
                'id' => $asset->id,
                'index' => 0,
                'prompt' => 'Make it lively',
            ],
        ],
    ])->assertOk();

    $user->refresh();

    expect($user->remaining_credit_amount)->toBe(490) // 10 credits for animation generation
        ->and(Asset::where('type', AssetType::AI_VIDEO)->count())->toBe(1);

    Queue::assertPushed(CreateAnimationJob::class);
});

test('users can fetch bulk animation status', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $parentAsset1 = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);
    $parentAsset2 = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::AI_IMAGE]);

    $animations = Asset::factory()
        ->for($user)
        ->aiVideo()
        ->forEachSequence(
            ['status' => AssetStatus::PROCESSING, 'parent_id' => $parentAsset1->id],
            ['status' => AssetStatus::SUCCESS, 'parent_id' => $parentAsset2->id],
            ['status' => AssetStatus::FAILED, 'parent_id' => $parentAsset2->id],
        )
        ->create();

    $animations->each(fn ($animation, $index) => $animation->videos()->attach($faceless->id, ['order' => $index]));

    [$animation1, $animation2, $animation3] = $animations->all();

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => $animations->pluck('id')->toArray(),
        ])
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3)
        ->and($response->json('data.0.id'))->toBe($animation1->id)
        ->and($response->json('data.0.status'))->toBe(AssetStatus::PROCESSING->value)
        ->and($response->json('data.0.parent_id'))->toBe($parentAsset1->id)
        ->and($response->json('data.1.id'))->toBe($animation2->id)
        ->and($response->json('data.1.status'))->toBe(AssetStatus::SUCCESS->value)
        ->and($response->json('data.2.id'))->toBe($animation3->id)
        ->and($response->json('data.2.status'))->toBe(AssetStatus::FAILED->value)
        ->and($response->json('requested'))->toBe(3)
        ->and($response->json('returned'))->toBe(3);
});

test('bulk status only returns animations (assets with parent_id)', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $regularAsset = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);
    $regularAsset->videos()->attach($faceless->id, ['order' => 0]);

    $parentAsset = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::AI_IMAGE]);
    $animation = Asset::factory()->for($user)->processing()->aiVideo()->create(['parent_id' => $parentAsset->id]);
    $animation->videos()->attach($faceless->id, ['order' => 1]);

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => [$regularAsset->id, $animation->id],
        ])
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($animation->id)
        ->and($response->json('requested'))->toBe(2)
        ->and($response->json('returned'))->toBe(1);
});

test('bulk status requires assets array', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assets']);
});

test('bulk status requires non-empty assets array', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assets']);
});

test('users cannot check bulk status of animations from other users faceless videos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $faceless = Faceless::factory()->for($otherUser)->create();

    $animation = Asset::factory()->for($otherUser)->processing()->aiVideo()->create();
    $animation->videos()->attach($faceless->id, ['order' => 0]);

    $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => [$animation->id],
        ])
        ->assertForbidden();
});

test('bulk status includes media relationships', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $parentAsset = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);
    $animation = Asset::factory()->for($user)->success()->aiVideo()->withMedia()->create(['parent_id' => $parentAsset->id]);
    $animation->videos()->attach($faceless->id, ['order' => 0]);

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => [$animation->id],
        ])
        ->assertOk();

    expect($response->json('data.0.media'))->toBeArray()
        ->and($response->json('data.0.media'))->not->toBeEmpty();
});

test('bulk status returns correct metadata when no animations found', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset1 = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::CUSTOM_IMAGE]);
    $asset2 = Asset::factory()->for($user)->withMedia()->create(['type' => AssetType::STOCK_VIDEO]);

    $asset1->videos()->attach($faceless->id, ['order' => 0]);
    $asset2->videos()->attach($faceless->id, ['order' => 1]);

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/media/animation/bulk-status", [
            'assets' => [$asset1->id, $asset2->id],
        ])
        ->assertOk();

    expect($response->json('data'))->toBeEmpty()
        ->and($response->json('requested'))->toBe(2)
        ->and($response->json('returned'))->toBe(0);
});
