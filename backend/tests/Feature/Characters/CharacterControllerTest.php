<?php

namespace Tests\Feature\Characters;

use App\Syllaby\Users\User;
use App\Syllaby\Characters\Genre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Characters\Character;
use Illuminate\Support\Facades\Event;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Characters\Enums\CharacterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);

    Event::fake([MediaHasBeenAddedEvent::class]);
});

describe('GET /v1/characters', function () {
    it('can list all characters', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        Character::factory(3)->ready()->active()->for($genre)->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson('/v1/characters')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'slug',
                        'description',
                        'gender',
                        'status',
                        'genre' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ])->assertJsonCount(3, 'data');
    });

    it('filters characters by genre', function () {
        $user = User::factory()->create();

        $genre1 = Genre::factory()->active()->create(['slug' => 'fantasy']);
        $genre2 = Genre::factory()->active()->create(['slug' => 'sci-fi']);

        Character::factory(2)->ready()->active()->forEachSequence(
            ['genre_id' => $genre1->id, 'user_id' => null],
            ['genre_id' => $genre2->id, 'user_id' => null],
        )->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson('/v1/characters?filter[genre]=fantasy')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters characters by type', function () {
        $user = User::factory()->create();

        $genre = Genre::factory()->active()->create();

        Character::factory()->ready()->active()->for($genre)->createQuietly();
        Character::factory()->ready()->active()->for($user)->for($genre)->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson('/v1/characters?filter[type]=system')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('excludes draft characters', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        Character::factory()->ready()->active()->for($genre)->createQuietly();
        Character::factory()->draft()->active()->for($genre)->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson('/v1/characters')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/v1/characters')->assertUnauthorized();
    });
});

describe('POST /v1/characters', function () {
    it('can create a new character', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        $image = UploadedFile::fake()->image('character.jpg');

        $this->actingAs($user, 'sanctum')->postJson('/v1/characters', [
            'image' => $image,
            'genre_id' => $genre->id,
            'name' => 'Test Character',
            'description' => 'A test character',
            'gender' => 'male',
            'traits' => ['brave', 'intelligent'],
            'age' => '25',
        ])->assertCreated()->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'name',
                'description',
                'gender',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('characters', [
            'name' => 'Test Character',
            'description' => 'A test character',
            'gender' => 'male',
            'user_id' => $user->id,
            'genre_id' => $genre->id,
            'status' => CharacterStatus::DRAFT,
        ]);
    });

    it('can create character with minimal data', function () {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('character.jpg');

        $this->actingAs($user, 'sanctum')->postJson('/v1/characters', [
            'image' => $image,
        ])->assertCreated();

        $this->assertDatabaseHas('characters', [
            'user_id' => $user->id,
            'status' => CharacterStatus::DRAFT,
        ]);
    });

    it('validates required image field', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/v1/characters', [
                'name' => 'Test Character',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('validates image file type and size', function () {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 5000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/v1/characters', [
                'image' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('validates genre_id exists', function () {
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('character.jpg');

        $this->actingAs($user, 'sanctum')
            ->postJson('/v1/characters', [
                'image' => $image,
                'genre_id' => 999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id']);
    });

    it('validates gender values', function () {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('character.jpg');

        $this->actingAs($user, 'sanctum')
            ->postJson('/v1/characters', [
                'image' => $image,
                'gender' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    });

    it('validates description length', function () {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('character.jpg');

        $this->actingAs($user, 'sanctum')
            ->postJson('/v1/characters', [
                'image' => $image,
                'description' => str_repeat('a', 501),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    it('requires authentication', function () {
        $this->postJson('/v1/characters', [
            'image' => UploadedFile::fake()->image('character.jpg'),
        ])->assertUnauthorized();
    });
});

describe('GET /v1/characters/{character}', function () {
    it('can show a specific character', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        $character = Character::factory()->for($user)->for($genre)->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson("/v1/characters/{$character->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'description',
                    'gender',
                    'status',
                    'genre',
                ],
            ])
            ->assertJsonPath('data.id', $character->id);
    });

    it('prevents showing other users characters', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/v1/characters/{$character->id}")
            ->assertForbidden();
    });

    it('allows showing system characters', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        $character = Character::factory()->for($genre)->createQuietly();

        $this->actingAs($user, 'sanctum')
            ->getJson("/v1/characters/{$character->id}")
            ->assertOk();
    });

    it('returns 404 for non-existent character', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/v1/characters/999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $character = Character::factory()->create();

        $this->getJson("/v1/characters/{$character->id}")->assertUnauthorized();
    });
});

describe('PUT /v1/characters/{character}', function () {
    it('can update a character', function () {
        $user = User::factory()->create();
        $genre = Genre::factory()->active()->create();

        $character = Character::factory()->for($user)->create(['name' => 'Original Name']);

        $this->actingAs($user, 'sanctum')->putJson("/v1/characters/{$character->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'gender' => 'female',
            'genre_id' => $genre->id,
            'traits' => ['updated', 'traits'],
            'age' => '30',
        ])->assertOk()->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'gender' => 'female',
            'genre_id' => $genre->id,
        ]);
    });

    it('can update character with partial data', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $this->actingAs($user, 'sanctum')->putJson("/v1/characters/{$character->id}", [
            'name' => 'Updated Name Only',
        ])->assertOk();

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'name' => 'Updated Name Only',
            'description' => 'Original description', // Should remain unchanged
        ]);
    });

    it('prevents updating other users characters', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')->putJson("/v1/characters/{$character->id}", [
            'name' => 'Attempted Update',
        ])->assertForbidden();
    });

    it('validates genre_id exists', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/v1/characters/{$character->id}", [
                'genre_id' => 999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id']);
    });

    it('validates gender values', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/v1/characters/{$character->id}", [
                'gender' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    });

    it('validates description length', function () {
        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/v1/characters/{$character->id}", [
                'description' => str_repeat('a', 501),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    it('requires authentication', function () {
        $character = Character::factory()->create();

        $this->putJson("/v1/characters/{$character->id}", [
            'name' => 'Update Attempt',
        ])->assertUnauthorized();
    });
});

describe('DELETE /v1/characters/{character}', function () {
    it('can delete a character', function () {
        Http::fake([
            '*' => Http::sequence()->push()->push()
        ]);

        $user = User::factory()->create();

        $character = Character::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/v1/characters/{$character->id}")
            ->assertNoContent();

        $this->assertModelMissing($character);
    });

    it('prevents deleting other users characters', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $character = Character::factory()->for($otherUser)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/v1/characters/{$character->id}")
            ->assertForbidden();

        $this->assertModelExists($character);
    });

    it('prevents deleting system characters', function () {
        $user = User::factory()->create();

        $character = Character::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/v1/characters/{$character->id}")
            ->assertForbidden();

        $this->assertModelExists($character);
    });

    it('returns 404 for non-existent character', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/v1/characters/999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $character = Character::factory()->create();

        $this->deleteJson("/v1/characters/{$character->id}")->assertUnauthorized();
    });
});
