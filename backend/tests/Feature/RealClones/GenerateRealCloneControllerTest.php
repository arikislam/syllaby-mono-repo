<?php

namespace Tests\Feature\RealClones;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use App\Syllaby\Speeches\Speech;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Speeches\Enums\SpeechStatus;
use App\Syllaby\Speeches\Vendors\Elevenlabs;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\RealClones\Enums\RealCloneStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use App\Syllaby\Speeches\Jobs\TriggerSpeechGeneration;
use App\Syllaby\RealClones\Jobs\TriggerRealCloneGeneration;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can generate a real clone and charge user credits', function () {
    Bus::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
    ]);

    $clone = RealClone::factory()->for($user)->create([
        'provider' => RealCloneProvider::D_ID->value,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertAccepted();

    Bus::assertChained([
        TriggerSpeechGeneration::class,
        TriggerRealCloneGeneration::class,
    ]);

    expect($response->json('data'))
        ->provider->toBe($clone->provider)
        ->script->toBe($clone->script)
        ->status->toBe(RealCloneStatus::GENERATING->value)
        ->and($user->remaining_credit_amount)->toBe(478);
});

it('starts to generate a video for a given real clone with d-id', function () {
    Queue::fake()->except([
        TriggerSpeechGeneration::class,
        TriggerRealCloneGeneration::class,
    ]);

    Event::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.d-id.com/clips' => Http::response(['id' => 'fake-id']),
        'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response('fake-audio-content'),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::D_ID->value,
    ]);

    $speech = Speech::factory()->for($clone)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertAccepted();

    expect($speech->fresh())
        ->status->toBe(SpeechStatus::COMPLETED);

    $this->assertDatabaseHas('media', [
        'model_id' => $speech->id,
        'model_type' => $speech->getMorphClass(),
    ]);

    expect($clone->fresh())
        ->provider_id->toBe('fake-id')
        ->status->toBe(RealCloneStatus::GENERATING);
});

it('starts to generate a video for a given real clone with fastvideo', function () {
    Queue::fake()->except([
        TriggerSpeechGeneration::class,
        TriggerRealCloneGeneration::class,
    ]);

    Event::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://fastvideo.p.rapidapi.com/createVideo' => Http::response(['video_id' => 'fake-id']),
        'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response('fake-audio-content'),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::FASTVIDEO->value,
    ]);

    $speech = Speech::factory()->for($clone)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertAccepted();

    expect($speech->fresh())
        ->status->toBe(SpeechStatus::COMPLETED);

    $this->assertDatabaseHas('media', [
        'model_id' => $speech->id,
        'model_type' => $speech->getMorphClass(),
    ]);

    expect($clone->fresh())
        ->provider_id->toBe('fake-id')
        ->status->toBe(RealCloneStatus::GENERATING);
});

it('starts to generate a video for a given real clone with heygen', function () {
    Queue::fake()->except([
        TriggerSpeechGeneration::class,
        TriggerRealCloneGeneration::class,
    ]);

    Event::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response('<fake-audio-content>'),
        'https://api.heygen.com/v1/video.webm' => Http::response(['data' => ['video_id' => 'fake-id']]),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    $speech = Speech::factory()->for($clone)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
        'status' => SpeechStatus::PROCESSING,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertAccepted();

    expect($speech->fresh())
        ->status->toBe(SpeechStatus::COMPLETED);

    $this->assertDatabaseHas('media', [
        'model_id' => $speech->id,
        'model_type' => $speech->getMorphClass(),
    ]);

    expect($clone->fresh())
        ->provider_id->toBe('fake-id')
        ->status->toBe(RealCloneStatus::GENERATING);
});

it('refunds whole credits if speech generation fail', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.elevenlabs.io/v1/*' => Http::response([], 503),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->recycle($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    Speech::factory()->for($clone)->recycle($user)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
        'status' => SpeechStatus::PROCESSING,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/real-clones/{$clone->id}/generate")
        ->assertAccepted();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 500,
    ]);
});

it('partially refund credits if speech is successful but real-clone fails', function () {
    // Event::fake();
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.elevenlabs.io/v1/*' => Http::response('test-voice'),
        'https://api.heygen.com/v1/video.webm' => Http::response([], 503),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->recycle($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    Speech::factory()->for($clone)->recycle($user)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
        'status' => SpeechStatus::PROCESSING,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/real-clones/{$clone->id}/generate")
        ->assertAccepted();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 493,
    ]);
});

it('skips speech generation when either voice or script were not changed', function () {
    Queue::fake()->except([
        TriggerSpeechGeneration::class,
        TriggerRealCloneGeneration::class,
    ]);

    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    Http::fake([
        'https://api.elevenlabs.io/v1/text-to-speech/*' => Http::response('<fake-audio-content>'),
        'https://api.heygen.com/v1/video.webm' => Http::response(['data' => ['video_id' => 'fake-id']]),
    ]);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->recycle($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    Speech::factory()->for($clone)->recycle($user)->create([
        'user_id' => $user->id,
        'voice_id' => $clone->voice_id,
        'status' => SpeechStatus::PROCESSING,
    ]);

    $clone->update(['hash' => $clone->hashes()]);

    $spy = $this->spy(Elevenlabs::class);

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/real-clones/{$clone->id}/generate")
        ->assertAccepted();

    $spy->shouldNotHaveReceived('generate');
    $spy->shouldNotHaveReceived('charge');
});

it('fails to start generating a real clone with insufficient credits', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->withoutCredits()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::INSUFFICIENT_CREDITS->value,
    ]);
});

it('fails to generate a real clone when the feature disabled', function () {
    Feature::define('real_clone', false);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});

it('fails to generate a real clone without enough storage', function () {
    Feature::define('video', true);
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'status' => RealCloneStatus::DRAFT,
        'provider' => RealCloneProvider::HEYGEN->value,
    ]);

    Media::factory()->for($clone, 'model')->create([
        'user_id' => $user->id,
        'size' => TestCase::BASE_MAX_ALLOWED_STORAGE + 200_000_000,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->postJson("/v1/real-clones/{$clone->id}/generate");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::REACH_PLAN_STORAGE_LIMIT->value,
    ]);
});
