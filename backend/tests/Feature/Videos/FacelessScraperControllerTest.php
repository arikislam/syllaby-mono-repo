<?php

namespace Tests\Feature\Videos;

use Exception;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Facades\Bus;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Scraper\Actions\ExtractImagesAction;
use App\Syllaby\Scraper\Actions\ExtractScriptAction;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
    Feature::define('video', true);
});

it('scrapes url and extracts script for amazon product', function () {
    Bus::fake();
    Chat::fake();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->urlBased()
        ->for(Video::factory()->draft()->for($user))
        ->for($user)
        ->create();

    $this->mock(ExtractScriptAction::class)->shouldReceive('handle')->once()->andReturn($faceless);

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [
        'url' => 'https://amazon.com/product',
        'duration' => 60,
        'style' => 'professional',
        'language' => 'en',
        'tone' => 'friendly',
    ])->assertOk();

    expect($response->json('data'))
        ->id->toBe($faceless->id)
        ->type->toBe(Faceless::URL_BASED);
});

it('validates required fields', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [])
        ->assertUnprocessable();

    expect($response->json('errors'))->toHaveKeys(['url', 'duration', 'style', 'language', 'tone']);
});

it('validates url format', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [
        'url' => 'invalid-url',
        'duration' => 60,
        'style' => 'professional',
        'language' => 'en',
        'tone' => 'friendly',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['url']);
});

it('requires authentication', function () {
    $faceless = Faceless::factory()->create();

    $response = $this->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [
        'url' => 'https://amazon.com/product',
        'duration' => 60,
        'style' => 'professional',
        'language' => 'en',
        'tone' => 'friendly',
    ]);

    $response->assertUnauthorized();
});

it('fails if user does not own the video', function () {
    $faceless = Faceless::factory()->create();

    $response = $this->actingAs(User::factory()->create())->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [
        'url' => 'https://amazon.com/product',
        'duration' => 60,
        'style' => 'professional',
        'language' => 'en',
        'tone' => 'friendly',
    ]);

    $response->assertForbidden();
});

it('handles scraping errors gracefully', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $this->mock(ExtractScriptAction::class)
        ->shouldReceive('handle')
        ->once()
        ->andThrow(new Exception('Failed to scrape URL'));

    $response = $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/scrape", [
        'url' => 'https://amazon.com/product',
        'duration' => 60,
        'style' => 'professional',
        'language' => 'en',
        'tone' => 'friendly',
    ]);

    $response->assertServerError()->assertJson(['message' => 'Failed to scrape URL']);
});
