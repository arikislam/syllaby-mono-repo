<?php

namespace Tests\Feature\Ideas;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Ideas\Enums\Networks;
use App\Http\Responses\ErrorCode as Code;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Assistants\Chat;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('allows users to discover ideas based on keyword search from keywordtool', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://api.keywordtool.io/v2-sandbox/quota' => Http::response(['limits' => [
            'minute' => ['quota' => 10, 'used' => 0, 'remaining' => 10],
            'daily' => ['quota' => 100, 'used' => 0, 'remaining' => 100],
        ]]),
        'https://api.keywordtool.io/v2-sandbox/search/suggestions/google' => Http::response(
            json_decode(file_get_contents(base_path('tests/Stubs/KeywordTool/suggestions.json')), true),
        ),
        'https://api.keywordtool.io/v2-sandbox/search/volume/google' => Http::response(
            json_decode(file_get_contents(base_path('tests/Stubs/KeywordTool/volume.json')), true),
        ),
    ]);

    $this->actingAs($user);
    $response = $this->postJson('/v1/ideas/discover', [
        'keyword' => 'web design',
        'network' => Networks::GOOGLE->value,
    ]);

    expect($response->json('data'))
        ->name->toBe('web design')
        ->slug->toBe('web-design')
        ->network->toBe(Networks::GOOGLE->value)
        ->source->toBe('keywordtool');
});

it('allows users to discover ideas based on keyword search from database', function () {
    Http::fake();

    $user = User::factory()->create();
    $keyword = Keyword::factory()->create([
        'name' => 'web development',
        'slug' => 'web-development',
        'network' => Networks::GOOGLE->value,
    ]);

    $this->actingAs($user);
    $response = $this->postJson('/v1/ideas/discover', [
        'keyword' => $keyword->name,
        'network' => Networks::GOOGLE->value,
    ]);

    Http::assertNothingSent();

    expect($response->json('data'))
        ->name->toBe($keyword->name)
        ->slug->toBe($keyword->slug)
        ->network->toBe(Networks::GOOGLE->value)
        ->source->toBe('keywordtool');
});

it('uses openai as fallback strategy to find ideas for a keyword', function () {
    Chat::fake();

    $plan = Plan::factory()->create();
    $user = User::factory()->withTrial($plan)->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/ideas/discover', [
        'keyword' => 'web design',
        'network' => Networks::GOOGLE->value,
    ])->assertOk();

    expect($response->json('data'))
        ->name->toBe('web design')
        ->slug->toBe('web-design')
        ->network->toBe(Networks::GOOGLE->value)
        ->source->toBe('openai');
});

it('charge credits when searching for a unknown keyword', function () {
    $user = User::factory()->create([
        'monthly_credit_amount' => 20,
        'remaining_credit_amount' => 20,
    ]);

    Http::fake([
        'https://api.keywordtool.io/v2-sandbox/quota' => Http::response(['limits' => [
            'minute' => ['quota' => 10, 'used' => 0, 'remaining' => 10],
            'daily' => ['quota' => 100, 'used' => 0, 'remaining' => 100],
        ]]),
        'https://api.keywordtool.io/v2-sandbox/search/suggestions/google' => Http::response(
            json_decode(file_get_contents(base_path('tests/Stubs/KeywordTool/suggestions.json')), true),
        ),
        'https://api.keywordtool.io/v2-sandbox/search/volume/google' => Http::response(
            json_decode(file_get_contents(base_path('tests/Stubs/KeywordTool/volume.json')), true),
        ),
    ]);

    $this->actingAs($user);
    $this->postJson('/v1/ideas/discover', [
        'keyword' => 'web design',
        'network' => Networks::GOOGLE->value,
    ]);

    $user = $user->refresh();
    expect($user->remaining_credit_amount)->toBe(5);
});

it('fails to discover ideas with insufficient credits', function () {
    Http::fake();

    $user = User::factory()->withoutCredits()->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/ideas/discover', [
        'keyword' => 'web design',
        'network' => Networks::GOOGLE->value,
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => Code::INSUFFICIENT_CREDITS->value,
    ]);

    Http::assertNothingSent();
});
