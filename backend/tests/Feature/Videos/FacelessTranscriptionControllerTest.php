<?php

namespace Tests\Feature\Videos;

use Mockery;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Faceless;
use App\Http\Responses\ErrorCode as Code;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Generators\DTOs\CaptionResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Transcribers\Transcriber;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can transcribe a faceless video and deduct credits', function () {
    Feature::define('video', true);

    $user = User::factory()->create([
        'remaining_credit_amount' => 500,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create();
    $audio = Media::factory()->for($faceless, 'model')->create([
        'user_id' => $user->id,
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $transcriber = Mockery::mock();
    Transcriber::shouldReceive('driver')->with('whisper')->andReturn($transcriber);
    $transcriber->shouldReceive('run')->once()->with($audio->getFullUrl(), ['language' => 'none'])
        ->andReturn(CaptionResponse::fromWhisper([
            'text' => 'Test transcription text',
            'chunks' => [
                ['text' => 'Test', 'timestamp' => [0.0, 0.5]],
                ['text' => 'transcription', 'timestamp' => [0.6, 1.2]],
                ['text' => 'text', 'timestamp' => [1.3, 1.8]],
            ],
        ]));

    $transcriber->shouldReceive('credits')->once()->andReturn(100);

    $this->actingAs($user);
    $response = $this->postJson("v1/videos/faceless/{$faceless->id}/transcriptions");

    $response->assertOk();

    expect($response->json('data'))
        ->is_transcribed->toBeTrue()
        ->script->toBe('Test transcription text')
        ->and($user->refresh()->remaining_credit_amount)->toBe(400);
});

it('fails to transcribe a faceless video with insufficient credits', function () {
    $user = User::factory()->withoutCredits()->create();

    $faceless = Faceless::factory()->create();
    Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 120],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("v1/videos/faceless/{$faceless->id}/transcriptions");

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::INSUFFICIENT_CREDITS->value,
    ]);
});

afterEach(fn () => Mockery::close());
