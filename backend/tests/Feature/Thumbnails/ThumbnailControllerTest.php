<?php

namespace App\Feature\Api\Thumbnails;

use Mockery\MockInterface;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Enums\AssetType;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Assets\Actions\CreateThumbnailAction;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can list thumbnails for authenticated user', function () {
    Feature::define('thumbnails', true);
    
    $user = User::factory()->create();

    $thumbnails = Asset::factory()->count(3)->recycle($user)->create([
        'user_id' => $user->id,
        'type' => AssetType::THUMBNAIL,
    ]);

    Asset::factory()->count(2)->create([
        'type' => AssetType::THUMBNAIL,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/thumbnails');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount($thumbnails->count());
});

it('requires authentication to list thumbnails', function () {
    $response = $this->getJson('v1/thumbnails');
    $response->assertUnauthorized();
});

it('can generate new thumbnails', function () {
    Feature::define('thumbnails', true);
    
    $user = User::factory()->create(['remaining_credit_amount' => 100]);

    $this->mock(CreateThumbnailAction::class, function (MockInterface $mock) use ($user) {
        $mock->shouldReceive('handle')->once()->andReturn(
            Asset::factory()->recycle($user)->count(3)->withMedia()->create([
                'type' => AssetType::THUMBNAIL,
            ])
        );
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/thumbnails', [
        'context' => 'A tech startup office environment',
        'text' => 'Innovation at work',
        'color' => '#FF5733',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'type',
                'status',
                'description',
            ],
        ],
    ]);

    expect($response->json('data'))->toHaveCount(3);
    expect($user->fresh()->remaining_credit_amount)->toBe(99);
});

it('can delete a thumbnail', function () {
    $user = User::factory()->create();

    $thumbnail = Asset::factory()->recycle($user)->create([
        'type' => AssetType::THUMBNAIL,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("v1/thumbnails/{$thumbnail->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('assets', ['id' => $thumbnail->id]);
});
