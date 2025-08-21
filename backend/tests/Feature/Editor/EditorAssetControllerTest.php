<?php

use App\Syllaby\Users\User;
use App\Syllaby\Editor\EditorAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the assets for the editor', function () {
    $user = User::factory()->create();

    EditorAsset::factory()->count(5)->create();

    $this->actingAs($user)->getJson('/v1/editor/assets')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'user_id', 'type', 'preview', 'key', 'value', 'active', 'created_at', 'updated_at'
                ]
            ],
        ]);
});

it('can filter assets by type', function () {
    $user = User::factory()->create();

    $font = EditorAsset::factory()->font()->create();

    EditorAsset::factory()->preset()->create();

    $this->actingAs($user)->getJson('/v1/editor/assets?filter[type]=font')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $font->id);
});

it('can paginate the assets', function () {
    $user = User::factory()->create();

    EditorAsset::factory()->count(15)->create();

    $this->actingAs($user)->getJson('/v1/editor/assets?per_page=10&page=2')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('caches the assets', function () {
    $user = User::factory()->create();

    EditorAsset::factory()->count(5)->create();

    $this->actingAs($user)->getJson('/v1/editor/assets');

    $key = sprintf("editor-assets:page-%s:per_page-%s:type-%s:user-%s", 1, 10, 'all', $user->id);

    expect(Cache::has($key))->toBeTrue();
});
