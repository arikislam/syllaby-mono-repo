<?php

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Folder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('creates a new folder', function () {
    $user = User::factory()->withDefaultFolder()->create();

    $this->actingAs($user)->postJson('/v1/folders', [
        'name' => 'Test Folder',
        'parent_id' => $user->folders()->firstWhere('name', 'Default')->resource->id,
    ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'model_id',
                'model_type',
                'model' => [
                    'id',
                    'user_id',
                    'name',
                    'color',
                ],
            ],
        ]);

    $this->assertDatabaseHas('folders', [
        'name' => 'Test Folder',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('resources', [
        'model_id' => Folder::firstWhere('name', 'Test Folder')->id,
        'model_type' => 'folder',
        'user_id' => $user->id,
    ]);
});

it('allows for tree list view of folders', function () {
    $user = User::factory()->create();

    // Folders - Level 1
    $lvl1folder1 = Folder::factory()->for($user)->create(['name' => 'Lvl 1 - Folder 1']);
    $lvl1folder2 = Folder::factory()->for($user)->create(['name' => 'Lvl 1 - Folder 2']);

    // Folders - Level 2
    $lvl2folder1 = Folder::factory()->for($user)->create(['name' => 'Lvl 2 - Folder 1']);
    $lvl2folder1->resource()->update(['parent_id' => $lvl1folder1->resource->id]);

    $lvl2folder2 = Folder::factory()->for($user)->create(['name' => 'Lvl 2 - Folder 2']);
    $lvl2folder2->resource()->update(['parent_id' => $lvl1folder1->resource->id]);

    $lvl2video1 = Video::factory()->for($user)->create();
    $lvl2video1->resource()->create(['parent_id' => $lvl2folder2->resource->id, 'user_id' => $user->id]);

    // Folders - Level 3
    $lvl3folder1 = Folder::factory()->for($user)->create(['name' => 'Lvl 3 - Folder 1']);
    $lvl3folder1->resource()->update(['parent_id' => $lvl2folder1->resource->id]);

    $lvl3video1 = Video::factory()->for($user)->create();
    $lvl3video1->resource()->create(['parent_id' => $lvl3folder1->resource->id, 'user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/folders')->assertOk();

    // Level 1 assertions
    $response->assertJsonCount(2, 'data')
        ->assertJsonCount(2, 'data.0.children')
        ->assertJsonCount(0, 'data.1.children')
        ->assertJsonPath('data.0.id', $lvl1folder1->resource->id)
        ->assertJsonPath('data.1.id', $lvl1folder2->resource->id);

    // Level 2 assertions
    $response
        ->assertJsonCount(1, 'data.0.children.0.children')
        ->assertJsonCount(0, 'data.0.children.1.children')
        ->assertJsonPath('data.0.children.0.id', $lvl2folder1->resource->id)
        ->assertJsonPath('data.0.children.1.id', $lvl2folder2->resource->id);

    // Level 3 assertions
    $response
        ->assertJsonCount(0, 'data.0.children.0.children.0.children')
        ->assertJsonPath('data.0.children.0.children.0.id', $lvl3folder1->resource->id);
});

it('requires authentication for creating new folder', function () {
    $response = $this->postJson('/v1/folders', []);

    $response->assertUnauthorized();
});

it('validates input for creating new folder', function () {
    $user = User::factory()->create();
    Folder::factory()->recycle($user)->create();

    $this->actingAs($user)->postJson('/v1/folders', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('defaults to root folder for creating new folder', function () {
    $user = User::factory()->create();
    $root = Folder::factory()->recycle($user)->create([
        'name' => 'Root Folder',
    ]);

    $this->actingAs($user)->postJson('/v1/folders', [
        'name' => 'Test Folder Two',
    ]);

    $this->assertDatabaseHas('folders', [
        'name' => 'Test Folder Two',
        'user_id' => $user->id,
    ]);

    $folder = Folder::latest('id')->first();
    $this->assertDatabaseHas('resources', [
        'model_id' => $folder->id,
        'model_type' => $folder->getMorphClass(),
        'user_id' => $user->id,
        'parent_id' => $root->resource->id,
    ]);
});

it('updates an existing folder name', function () {
    $user = User::factory()->withDefaultFolder()->create();

    $folder = Folder::factory()->for($user)->create(['name' => 'Test Folder']);

    $this->actingAs($user)
        ->putJson("/v1/folders/{$folder->id}", ['name' => 'Updated Folder Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Folder Name');

    $this->assertDatabaseHas('folders', [
        'id' => $folder->id,
        'name' => 'Updated Folder Name',
    ]);
});

it('requires authentication for update', function () {
    $folder = Folder::factory()->create();

    $response = $this->putJson("/v1/folders/{$folder->id}", []);

    $response->assertUnauthorized();
});

it('validates input for update', function () {
    $user = User::factory()->create();

    $folder = Folder::factory()->for($user)->create();

    $this->actingAs($user)
        ->putJson("/v1/folders/{$folder->id}", ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('prevents updating folders of other users', function () {
    $folder = Folder::factory()->create();

    $this->actingAs(User::factory()->create())->putJson("/v1/folders/{$folder->id}", [
        'name' => 'Attempt to update',
    ])->assertForbidden();
});
