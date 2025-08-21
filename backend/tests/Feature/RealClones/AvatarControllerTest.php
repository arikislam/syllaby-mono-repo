<?php

namespace Tests\Feature\RealClones;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Carbon;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\RealClones\RealClone;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('fetches a list of all available avatars', function () {
    Feature::define('video', true);

    Carbon::setTestNow(now());

    $user = User::factory()->create();
    $john = User::factory()->create();

    $avatars = Avatar::factory()->count(4)->state(new Sequence(
        ['user_id' => null, 'type' => Avatar::STANDARD, 'updated_at' => now()->subSeconds(1)],
        ['user_id' => $user->id, 'type' => Avatar::REAL_CLONE, 'updated_at' => now()->subSeconds(2)],
        ['user_id' => $user->id, 'type' => Avatar::REAL_CLONE, 'updated_at' => now()->subSeconds(3)],
        ['user_id' => $john->id, 'type' => Avatar::REAL_CLONE, 'updated_at' => now()->subSeconds(4)],
    ))->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/real-clones/avatars');

    $response->assertOk();

    $this->assertCount(3, $response->json('data'));
    collect($response->json('data'))->each(function ($avatar, $index) use ($avatars) {
        $expected = $avatars[$index];

        expect(data_get($avatar, 'provider'))->toBe(data_get($expected, 'provider.value'))
            ->and(data_get($avatar, 'id'))->toBe(data_get($expected, 'id'))
            ->and(data_get($avatar, 'name'))->toBe(data_get($expected, 'name'))
            ->and(data_get($avatar, 'name'))->toBe(data_get($expected, 'name'))
            ->and(data_get($avatar, 'gender'))->toBe(data_get($expected, 'gender'))
            ->and(data_get($avatar, 'preview'))->toBe(data_get($expected, 'preview_url'));
    });
});

it('fails with a 403 when fetching a list of avatars with feature disabled', function () {
    Feature::define('real_clone', false);

    $user = User::factory()->create();
    Avatar::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/real-clones/avatars');

    $response->assertForbidden();
});

it('fetches most popular avatars in list avatars response', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $avatar = Avatar::factory()->create([
        'user_id' => null,
        'type' => Avatar::STANDARD,
    ]);

    RealClone::factory()->count(11)->recycle($avatar)->create();

    $response = $this->actingAs($user)->getJson('v1/real-clones/avatars');

    $response->assertOk()->assertJsonFragment([
        'popular' => [$avatar->id => 11],
    ]);

    $this->assertCount(1, $response->json('data'));
    $this->assertCount(1, $response->json('popular'));
});

it('doesnt pull real clone avatars in popular avatars', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $avatar = Avatar::factory()->for($user)->create([
        'type' => Avatar::REAL_CLONE,
    ]);

    RealClone::factory()->count(11)->recycle($avatar)->create();

    $response = $this->actingAs($user)->getJson('v1/real-clones/avatars');

    $response->assertOk()->assertJsonFragment([
        'popular' => [],
    ]);
});

it('allows users to delete their own avatars', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $avatar = Avatar::factory()->for($user)->create([
        'type' => Avatar::PHOTO,
    ]);

    $media = Media::factory()->for($avatar, 'model')->create();

    $this->actingAs($user);
    $response = $this->deleteJson("v1/real-clones/avatars/{$avatar->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('avatars', [
        'id' => $avatar->id,
    ]);

    $this->assertDatabaseMissing('media', [
        'id' => $media->id,
        'model_id' => $avatar->id,
        'model_type' => $avatar->getMorphClass(),
    ]);
});

it('fails deleting other users avatars', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $avatar = Avatar::factory()->create([
        'type' => Avatar::PHOTO,
    ]);

    $this->actingAs($user);
    $response = $this->deleteJson("v1/real-clones/avatars/{$avatar->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('avatars', ['id' => $avatar->id]);
});
