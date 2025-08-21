<?php

namespace App\Feature\Api\Templates;

use App\Syllaby\Users\User;
use App\Syllaby\Tags\Tag;
use App\Syllaby\Templates\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can display a list of all templates', function () {
    $user = User::factory()->create();

    $templates = Template::factory()->count(4)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/templates');

    $response->assertOk();

    $expected = $templates->pluck('id')->toArray();
    $actual = array_column($response->json('data'), 'id');

    expect($expected)->toBe($actual);
});

it('can display a list of only video templates', function () {
    $user = User::factory()->create();

    Template::factory()->count(5)->sequence(
        ['user_id' => $user->id, 'type' => 'video'],
        ['user_id' => null, 'type' => 'video'],
        ['user_id' => $user->id, 'type' => 'article'],
        ['user_id' => null, 'type' => 'video'],
        ['user_id' => $user->id, 'type' => 'video']
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/templates?filter[type]=video');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(4);
});

it('can filter video templates by tags', function () {
    $user = User::factory()->create();

    $finance = Tag::factory()->create(['slug' => 'finance']);
    $ecommerce = Tag::factory()->create(['slug' => 'ecommerce']);

    Template::factory()->hasAttached($ecommerce)->create(['type' => 'article']);
    Template::factory()->count(2)->hasAttached($ecommerce)->create(['type' => 'video']);
    Template::factory()->count(2)->sequence(
        ['type' => 'video'],
        ['type' => 'article'],
    )->hasAttached($finance)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/templates?filter[type]=video&filter[tag]=ecommerce');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('can filter video templates by aspect ratio', function () {
    $user = User::factory()->create();

    Template::factory()->count(3)->sequence(
        ['type' => 'article'],
        ['type' => 'video', 'metadata' => ['aspect_ratio' => '16:9']],
        ['type' => 'video', 'metadata' => ['aspect_ratio' => '9:16']],
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/templates?filter[type]=video&filter[ratio]=16:9');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('can display a list of only authenticated user templates', function () {
    $user = User::factory()->create();
    $john = User::factory()->create();

    Template::factory()->count(5)->sequence(
        ['user_id' => null, 'type' => 'video'],
        ['user_id' => $user->id, 'type' => 'article'],
        ['user_id' => $user->id, 'type' => 'video'],
        ['user_id' => $john->id, 'type' => 'video'],
        ['user_id' => $john->id, 'type' => 'article']
    )->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/templates');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('can display a single template', function () {
    $user = User::factory()->create();

    $template = Template::factory()->video()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("v1/templates/{$template->id}");

    $response->assertOk();
    expect($response->json('data'))->id->toBe($template->id);
});
