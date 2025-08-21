<?php

namespace Tests\Feature\Generators;

use Tests\TestCase;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Generators\Generator;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use App\Http\Responses\ErrorCode as Code;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can generate a real clone video script', function () {
    Chat::fake();
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create([
        'script' => null,
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/real-clones/{$clone->id}/scripts", [
        'tone' => 'casual',
        'style' => 'friendly',
        'language' => 'ENGLISH',
        'length' => 30,
        'topic' => 'Best trips to travel',
    ]);

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->script->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE)
        ->and($response->json('data.generator'))
        ->style->toBe('friendly')
        ->tone->toBe('casual')
        ->length->toBe('30')
        ->topic->toBe('Best trips to travel')
        ->language->toBe('ENGLISH');
});

it('can update an already generated script', function () {
    Chat::fake();
    Feature::define('video', true);

    $user = User::factory()->create();

    $clone = RealClone::factory()->for($user)->create([
        'script' => 'Lorem Ipsum',
    ]);

    Generator::factory()->for($clone, 'model')->create([
        'tone' => 'upbeat',
        'style' => 'explainer',
        'language' => 'ENGLISH',
        'length' => '30',
        'topic' => 'Lorem Ipsum',
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/real-clones/{$clone->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'length' => 60,
        'topic' => 'Best trips to travel',
    ]);

    expect($response->json('data'))
        ->id->toBe($clone->id)
        ->script->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE)
        ->and($response->json('data.generator'))
        ->style->toBe('tutorial')
        ->tone->toBe('funny')
        ->length->toBe('60')
        ->topic->toBe('Best trips to travel')
        ->language->toBe('ENGLISH');
});

it('fails to generate a script if user doesnt owns the real clone', function () {
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/real-clones/{$clone->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'length' => 60,
        'topic' => 'Best trips to travel',
    ]);

    $response->assertForbidden();
});

it('fails to generate script when user doesnt have enough credits', function () {
    Feature::define('video', true);

    $user = User::factory()->withoutCredits()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/real-clones/{$clone->id}/scripts");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::INSUFFICIENT_CREDITS->value,
    ]);
});

it('charge credits when user generates a script', function () {
    Chat::fake();
    Feature::define('video', true);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $this->putJson("/v1/real-clones/{$clone->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'length' => 60,
        'topic' => 'Best trips to travel',
    ]);

    $history = CreditHistory::first();
    $generator = $clone->generator;

    $this->assertDatabaseCount(CreditHistory::class, 1);

    expect($history)
        ->creditable_id->toBe($generator->id)
        ->creditable_type->toBe('generator')
        ->description->toBe(CreditEventEnum::CONTENT_PROMPT_REQUESTED->value)
        ->and($user->fresh())
        ->remaining_credit_amount->toBe($user->monthly_credit_amount - $history->amount);
});

it('fails to generate a script when the feature is disabled', function () {
    Feature::define('real_clone', false);

    $user = User::factory()->create();
    $clone = RealClone::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->putJson("/v1/real-clones/{$clone->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'length' => 60,
        'topic' => 'Best trips to travel',
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::FEATURE_NOT_ALLOWED->value,
    ]);
});

it('can generate a faceless video-script', function () {
    Chat::fake();

    Feature::define('video', true);

    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $response = $this->actingAs($user, 'sanctum')->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'casual',
        'style' => 'friendly',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertOk();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->script->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE)
        ->and($response->json('data.generator'))
        ->style->toBe('friendly')
        ->tone->toBe('casual')
        ->topic->toBe('Best trips to travel')
        ->language->toBe('ENGLISH')
        ->and($response->json('data.video.title'))->toBe('Best trips to travel');
});

it('can update an already generated faceless script', function () {
    Chat::fake();

    Feature::define('video', true);

    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    Generator::factory()->for($faceless, 'model')->create([
        'tone' => 'upbeat',
        'style' => 'explainer',
        'language' => 'ENGLISH',
        'topic' => 'Lorem Ipsum',
    ]);

    $response = $this->actingAs($user, 'sanctum')->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertOk();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->script->toBe(TestCase::OPEN_AI_MOCKED_RESPONSE)
        ->and($response->json('data.generator'))
        ->style->toBe('tutorial')
        ->tone->toBe('funny')
        ->length->toBeNull()
        ->topic->toBe('Best trips to travel')
        ->language->toBe('ENGLISH')
        ->and($response->json('data.video.title'))->toBe('Best trips to travel');
});

it('fails to generate a faceless script if user doesnt owns', function () {
    Feature::define('video', true);

    $faceless = Faceless::factory()->create();

    $this->actingAs(User::factory()->create())->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertForbidden();
});

it('fails to generate a faceless script if it is rendering', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)
        ->for(Video::factory()->rendering()->for($user)->create())
        ->create();

    $this->actingAs($user)->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertForbidden();
});

it('fails to generate a faceless script if it is syncing', function () {
    Feature::define('video', true);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)
        ->for(Video::factory()->syncing()->for($user)->create())
        ->create();

    $this->actingAs($user)->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertForbidden();
});

it('fails to generate faceless script without enough credits', function () {
    Feature::define('video', true);

    $user = User::factory()->withoutCredits()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/v1/videos/faceless/{$faceless->id}/scripts")
        ->assertForbidden()->assertJsonFragment(['code' => Code::INSUFFICIENT_CREDITS->value]);
});

it('charge credits when user generates a faceless script', function () {
    Chat::fake();

    Feature::define('video', true);

    $user = User::factory()->create();
    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum')->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertOk();

    $this->assertDatabaseCount(CreditHistory::class, 1);

    expect($history = CreditHistory::first())
        ->creditable_id->toBe($faceless->generator->id)
        ->creditable_type->toBe('generator')
        ->description->toBe(CreditEventEnum::CONTENT_PROMPT_REQUESTED->value)
        ->and($user->fresh())
        ->remaining_credit_amount->toBe($user->monthly_credit_amount - $history->amount);
});

it('fails to generate a faceless script with feature disabled', function () {
    Feature::define('video', false);

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $this->actingAs($user, 'sanctum')->putJson("/v1/videos/faceless/{$faceless->id}/scripts", [
        'tone' => 'funny',
        'style' => 'tutorial',
        'language' => 'ENGLISH',
        'topic' => 'Best trips to travel',
        'duration' => 60,
    ])->assertForbidden()->assertJsonFragment(['code' => Code::FEATURE_NOT_ALLOWED->value]);
});
