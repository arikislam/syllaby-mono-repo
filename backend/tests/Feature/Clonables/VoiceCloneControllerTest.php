<?php

namespace Tests\Feature\Clonables;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Speeches\Voice;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Clonables\Enums\CloneStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Clonables\Jobs\ProcessClonedVoiceJob;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('creates a voice clone intent and upload samples', function () {
    Queue::fake();
    Feature::define('max_voice_clones', 3);
    Event::fake([MediaHasBeenAddedEvent::class]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/clones/voices', [
        'name' => 'John Doe',
        'gender' => 'male',
        'description' => null,
        'provider' => 'elevenlabs',
        'terms' => true,
        'samples' => [
            UploadedFile::fake()->create('voice-sample-one.mp3', 5, 'audio/mpeg'),
            UploadedFile::fake()->create('voice-sample-two.mp3', 5, 'audio/mpeg'),
        ],
    ]);

    $response->assertCreated();
    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->model_type->toBe(Relation::getMorphAlias(Voice::class))
        ->status->toBe(CloneStatus::PENDING->value);

    $this->assertDatabaseCount('media', 2);
    Queue::assertPushed(ProcessClonedVoiceJob::class);
});

it('fails to create a voice clone intent when max voice clones is reached', function () {
    Feature::define('max_voice_clones', 0);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson('/v1/clones/voices');

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::REACH_PLAN_LIMIT->value,
    ]);
});

it('updates an existing voice clone intent', function () {
    $user = User::factory()->create();
    $voice = Voice::factory()->for($user)->create();

    $clone = Clonable::factory()->for($user)->create([
        'purchase_id' => null,
        'status' => CloneStatus::PENDING,
        'model_id' => $voice->id,
        'model_type' => $voice->getMorphClass(),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/clones/{$clone->id}/voices", [
        'name' => 'Jane Doe',
        'gender' => 'female',
        'description' => 'Lorem Ipsum',
        'provider' => 'elevenlabs',
        'terms' => true,
    ]);

    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->model_type->toBe($voice->getMorphClass())
        ->purchase_id->toBe(null)
        ->model_id->toBe($voice->id)
        ->status->toBe(CloneStatus::PENDING->value);
});

it('fails to update another user voice clone intent', function () {
    $user = User::factory()->create();
    $clone = Clonable::factory()->create([
        'purchase_id' => null,
        'status' => CloneStatus::PENDING,
        'model_type' => Relation::getMorphAlias(Voice::class),
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->patchJson("/v1/clones/{$clone->id}/voices", [
        'name' => 'Jane Doe',
        'gender' => 'female',
        'description' => 'Lorem Ipsum',
        'provider' => 'elevenlabs',
        'terms' => true,
    ]);

    $response->assertForbidden();
});
