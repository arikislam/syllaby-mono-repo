<?php

namespace Tests\Feature\RealClones;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Generators\Generator;
use App\Syllaby\RealClones\RealClone;
use App\Http\Responses\ErrorCode as Code;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can display a single real clone', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/real-clones/$clone->id");

    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->user_id->toBe($user->id);
});

it('fails to display a real clone with feature disabled', function () {
    Feature::define('real_clone', false);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/real-clones/$clone->id");

    $response->assertForbidden();
    $response->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});

it('responds with a null resource if real clone not exists', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/real-clones/0');

    $response->assertOk();
    expect($response->json('data'))->toBe(null);
});

it('creates a draft real clone with all fields present on the request', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();
    $avatar = Avatar::factory()->create();
    $voice = Voice::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/real-clones', [
        'voice_id' => $voice->id,
        'avatar_id' => $avatar->id,
        'footage_id' => $footage->id,
        'provider' => $avatar->provider->value,
    ]);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->footage_id->toBe($footage->id)
        ->voice_id->toBe($voice->id)
        ->avatar_id->toBe($avatar->id)
        ->provider->toBe($avatar->provider->value)
        ->status->toBe(RealCloneStatus::DRAFT->value);
});

it('creates a draft real clone with only footage_id present on the request', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/real-clones', [
        'footage_id' => $footage->id,
    ]);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->footage_id->toBe($footage->id)
        ->voice_id->toBe(null)
        ->avatar_id->toBe(null)
        ->provider->toBe(null)
        ->status->toBe(RealCloneStatus::DRAFT->value);
});

it('fails to create a draft real clone for another user video', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $footage = Footage::factory()->create();
    $avatar = Avatar::factory()->create();
    $voice = Voice::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/real-clones', [
        'footage_id' => $footage->id,
        'provider' => $avatar->provider->value,
        'voice_id' => $voice->provider_id,
        'avatar_id' => $avatar->provider_id,
    ]);

    $response->assertUnprocessable()->assertJson(function (AssertableJson $json) {
        $json->has('errors.footage_id')->etc();
    });
});

it('fails to create a draft real clone with feature disabled', function () {
    Feature::define('real_clone', false);

    $user = User::factory()->create();

    $footage = Footage::factory()->for($user)->create();
    $avatar = Avatar::factory()->create();
    $voice = Voice::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('v1/real-clones', [
        'voice_id' => $voice->id,
        'avatar_id' => $avatar->id,
        'footage_id' => $footage->id,
        'provider' => $avatar->provider->value,
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});

it('updates a given real clone details', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    [$voiceOne, $voiceTwo] = Voice::factory()->count(2)->create();
    [$avatarOne, $avatarTwo] = Avatar::factory()->count(2)->create();

    $clone = RealClone::factory()->for($user)->create([
        'voice_id' => $voiceOne->id,
        'avatar_id' => $avatarOne->id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/real-clones/$clone->id", [
        'status' => RealCloneStatus::COMPLETED,
        'voice_id' => $voiceTwo->id,
        'avatar_id' => $avatarTwo->id,
        'provider' => $avatarTwo->provider->value,
    ]);

    $response->assertOk();
    expect($response->json('data'))
        ->voice_id->toBe($voiceTwo->id)
        ->avatar_id->toBe($avatarTwo->id)
        ->status->toBe(RealCloneStatus::DRAFT->value);
});

it('fails to update a real clone details from another user', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/real-clones/$clone->id");

    $response->assertForbidden();
});

it('fails to update a real clone details with feature disabled', function () {
    Feature::define('real_clone', false);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/real-clones/$clone->id");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});

it('deletes a real clone and all its dependencies', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create();
    $media = Media::factory()->for($clone, 'model')->create();

    $generator = Generator::factory()->for($clone, 'model')->create();
    $speech = Speech::factory()->for($clone)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("v1/real-clones/$clone->id");

    $response->assertNoContent();

    $this->assertDatabaseMissing('real_clones', ['id' => $clone->id]);
    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    $this->assertDatabaseMissing('generators', ['id' => $generator->id]);
    $this->assertDatabaseMissing('speeches', ['id' => $speech->id]);
});

it('fails to deletes other user real_clone', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->deleteJson("v1/real-clones/$clone->id");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::GEN_FORBIDDEN->value,
    ]);
});
