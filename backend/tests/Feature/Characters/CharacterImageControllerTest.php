<?php

namespace Tests\Feature\Characters;

use App\Syllaby\Users\User;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    Event::fake([MediaHasBeenAddedEvent::class]);
});

it('can update character image', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $image = UploadedFile::fake()->image('new-character.jpg');

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [
            'image' => $image,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'name',
            ],
        ]);

    expect($character->fresh()->getFirstMedia('reference'))->not->toBeNull();
});

it('validates required image field', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image file type and size', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 5000),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image size limit', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [
            'image' => UploadedFile::fake()->image('large.jpg')->size(11000),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('prevents uploading image for other users characters', function () {
    $user = User::factory()->create();

    $character = Character::factory()->for($user)->create();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [
            'image' => UploadedFile::fake()->image('character.jpg'),
        ])->assertForbidden();
});

it('prevents uploading image for system characters', function () {
    $user = User::factory()->create();

    $character = Character::factory()->create();

    $image = UploadedFile::fake()->image('character.jpg');

    $this->actingAs($user, 'sanctum')
        ->postJson("/v1/characters/{$character->id}/image", [
            'image' => $image,
        ])
        ->assertForbidden();
});

it('returns 404 for non-existent character', function () {
    $user = User::factory()->create();

    $image = UploadedFile::fake()->image('character.jpg');

    $this->actingAs($user, 'sanctum')
        ->postJson('/v1/characters/999/image', [
            'image' => $image,
        ])->assertNotFound();
});

it('requires authentication', function () {
    $character = Character::factory()->create();

    $image = UploadedFile::fake()->image('character.jpg');

    $this->postJson("/v1/characters/{$character->id}/image", [
        'image' => $image,
    ])->assertUnauthorized();
});
