<?php

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Bookmarks\Bookmark;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('lists contents of root folder', function () {
    $user = User::factory()->create();

    [$default, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Default'], ['name' => 'Test Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $default->resource->id]);

    $video = Video::factory()->recycle($user)->completed()->create();

    $video->resource()->create(['parent_id' => $default->resource->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/v1/folders/resources')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('can sort folders by name ascending', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);

    [$f1, $f2, $f3] = Folder::factory()->recycle($user)->forEachSequence(
        ['name' => 'C Folder'],
        ['name' => 'A Folder'],
        ['name' => 'B Folder']
    )->create();

    collect([$f1, $f2, $f3])->each(function ($folder) use ($root, $user) {
        $folder->resource()->update([
            'user_id' => $user->id,
            'parent_id' => $root->resource->id,
        ]);
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/folders/resources?sort=name');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('A Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('B Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('C Folder')
    );
});

it('can sort folders by name descending', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);

    [$f1, $f2, $f3] = Folder::factory()->recycle($user)->forEachSequence(
        ['name' => 'C Folder'],
        ['name' => 'A Folder'],
        ['name' => 'B Folder']
    )->create();

    collect([$f1, $f2, $f3])->each(function ($folder) use ($root, $user) {
        $folder->resource()->update([
            'user_id' => $user->id,
            'parent_id' => $root->resource->id,
        ]);
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/folders/resources?sort=-name');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('C Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('B Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('A Folder')
    );
});

it('can sort folders by date ascending', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);

    [$f1, $f2, $f3] = Folder::factory()->recycle($user)->forEachSequence(
        ['name' => 'A Folder'],
        ['name' => 'B Folder'],
        ['name' => 'C Folder'],
    )->create();

    collect([$f1, $f2, $f3])->each(function ($folder) use ($root, $user) {
        $folder->resource()->update([
            'user_id' => $user->id,
            'parent_id' => $root->resource->id,
        ]);
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/folders/resources?sort=date');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('A Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('B Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('C Folder'),
    );
});

it('can sort folders by date descending', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);

    [$f1, $f2, $f3] = Folder::factory()->recycle($user)->forEachSequence(
        ['name' => 'A Folder'],
        ['name' => 'B Folder'],
        ['name' => 'C Folder'],
    )->create();

    collect([$f1, $f2, $f3])->each(function ($folder) use ($root, $user) {
        $folder->resource()->update([
            'user_id' => $user->id,
            'parent_id' => $root->resource->id,
        ]);
    });

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/folders/resources?sort=-date');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('C Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('B Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('A Folder'),
    );
});

it('can sort folders by bookmarks first', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);

    [$f1, $f2, $f3] = Folder::factory()->recycle($user)->forEachSequence(
        ['name' => 'A Folder'],
        ['name' => 'B Folder'],
        ['name' => 'C Folder'],
    )->create();

    collect([$f1, $f2, $f3])->each(function ($folder) use ($root, $user) {
        $folder->resource()->update([
            'user_id' => $user->id,
            'parent_id' => $root->resource->id,
        ]);
    });

    Bookmark::factory()->recycle($user)->create([
        'model_id' => $f1->id,
        'model_type' => 'folder',
    ]);

    Bookmark::factory()->recycle($user)->create([
        'model_id' => $f3->id,
        'model_type' => 'folder',
    ]);

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('/v1/folders/resources?sort=-bookmarked');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(3)->sequence(
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('A Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('C Folder'),
        fn ($folder) => expect(Arr::get($folder->value, 'model.name'))->toBe('B Folder'),
    );
});

it('list only folders as content from resources', function () {
    $user = User::factory()->create();

    [$default, $folder] = Folder::factory()->recycle($user)
        ->forEachSequence(['name' => 'Default'], ['name' => 'Test Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $default->resource->id]);

    $video = Video::factory()->recycle($user)->completed()->create();
    $video->resource()->create(['parent_id' => $default->resource->id, 'user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/folders/resources?include=folders');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('returns ancestors of a folder', function () {
    $user = User::factory()->create();

    [$default, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Default'], ['name' => 'Test Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $default->resource->id]);

    $video = Video::factory()->recycle($user)->completed()->create();

    $video->resource()->create(['parent_id' => $folder->resource->id, 'user_id' => $user->id]);

    $nested = Folder::factory()->recycle($user)->create(['name' => 'Nested Folder']);

    $nested->resource()->update(['parent_id' => $folder->resource->id]);

    $response = $this->actingAs($user)->getJson("/v1/folders/resources?id={$nested->resource->id}")->assertOk();

    expect($response->json('breadcrumbs'))->toHaveCount(3);
});

it('can move resources to a destination folder', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Root']);
    $destination = Folder::factory()->recycle($user)->create(['name' => 'Destination']);
    $destination->resource()->update(['parent_id' => $root->resource->id, 'user_id' => $user->id]);

    $folder = Folder::factory()->recycle($user)->create(['name' => 'Folder to Move']);
    $folder->resource()->update(['parent_id' => $root->resource->id, 'user_id' => $user->id]);

    $video = Video::factory()->recycle($user)->completed()->create();
    $video->resource()->create(['parent_id' => $root->resource->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson("/v1/folders/resources/{$destination->resource->id}/move", [
        'resources' => [$folder->resource->id, $video->resource->id],
    ])->assertOk();

    expect($response->json('data.children'))->toHaveCount(2);

    $this->assertDatabaseHas('resources', [
        'id' => $folder->resource->id,
        'parent_id' => $destination->resource->id,
    ]);

    $this->assertDatabaseHas('resources', [
        'id' => $video->resource->id,
        'parent_id' => $destination->resource->id,
    ]);
});

it('cannot move resources to another users folder', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $folder1 = Folder::factory()->recycle($user1)->create(['name' => 'User 1 Folder']);
    $folder2 = Folder::factory()->recycle($user2)->create(['name' => 'User 2 Folder']);

    $this->actingAs($user1)->putJson("/v1/folders/resources/{$folder2->resource->id}/move", [
        'resources' => [$folder1->resource->id],
    ])->assertUnprocessable()->assertJsonValidationErrors(['destination' => __('folders.move-resource')]);
});

it('cant move resources to a non-folder destination', function () {
    $user = User::factory()->create();

    [$root, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $root->resource->id]);

    $video = Video::factory()->recycle($user)->completed()->create();
    $video->resource()->create(['parent_id' => $root->resource->id, 'user_id' => $user->id]);

    $this->actingAs($user)->putJson("/v1/folders/resources/{$video->resource->id}/move", [
        'resources' => [$folder->resource->id],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['destination' => __('folders.move-non-folder')]);
});

it('cant move root folder', function () {
    $user = User::factory()->create();

    [$root, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $root->resource->id]);

    $this->actingAs($user)->putJson("/v1/folders/resources/{$folder->resource->id}/move", [
        'resources' => [$root->resource->id],
    ])->assertUnprocessable();
});

it('can delete an empty folder', function () {
    $user = User::factory()->create();

    [$root, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder to Delete'])
        ->create();

    $folder->resource()->update(['parent_id' => $root->resource->id]);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$folder->resource->id]])
        ->assertNoContent();

    $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    $this->assertDatabaseMissing('resources', ['id' => $folder->resource->id]);
});

it('cannot delete a folder with children', function () {
    $user = User::factory()->create();

    [$root, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder with Child'])
        ->create();

    $folder->resource()->update(['parent_id' => $root->resource->id]);

    $child = Folder::factory()->recycle($user)->create(['name' => 'Child Folder']);
    $child->resource()->update(['parent_id' => $folder->resource->id]);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$folder->resource->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['resources' => __('folders.delete-folder-with-children')]);

    $this->assertDatabaseHas('folders', ['id' => $folder->id]);
    $this->assertDatabaseHas('resources', ['id' => $folder->resource->id]);
});

it('cannot delete the root folder', function () {
    $user = User::factory()->create();

    $root = Folder::factory()->recycle($user)->create(['name' => 'Default']);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$root->resource->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['resources' => __('folders.delete-default-folder')]);

    $this->assertDatabaseHas('resources', ['id' => $root->resource->id]);
    $this->assertDatabaseHas('folders', ['id' => $root->id]);
});

it('can delete a video resource with all its components', function () {
    $user = User::factory()->create();

    $default = Folder::factory()->recycle($user)->create(['name' => 'Default']);

    $video = Video::factory()->recycle($user)->completed()->create();
    $video->resource()->create(['parent_id' => $default->resource->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$video->resource->id]])
        ->assertNoContent();

    $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    $this->assertDatabaseMissing('resources', ['id' => $video->resource->id]);
});

it('cannot delete another users folder', function () {
    $folder = Folder::factory()->create(['name' => 'User 2 Folder']);

    $this->actingAs(User::factory()->create())
        ->deleteJson('/v1/folders/resources', ['resources' => [$folder->resource->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['resources' => __('folders.invalid-resource')]);

    $this->assertDatabaseHas('folders', ['id' => $folder->id]);
    $this->assertDatabaseHas('resources', ['id' => $folder->resource->id]);
});

it('can bulk delete folders', function () {
    $user = User::factory()->create();

    [$root, $f1, $f2, $f3] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder 1'], ['name' => 'Folder 2'], ['name' => 'Folder 3'])
        ->create();

    $f1->resource()->update(['parent_id' => $root->resource->id]);
    $f2->resource()->update(['parent_id' => $root->resource->id]);
    $f3->resource()->update(['parent_id' => $root->resource->id]);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$f1->resource->id, $f2->resource->id]])
        ->assertNoContent();

    $this->assertDatabaseHas('folders', ['id' => $f3->id]);
    $this->assertDatabaseMissing('folders', ['id' => $f1->id]);
    $this->assertDatabaseMissing('folders', ['id' => $f2->id]);

    $this->assertDatabaseHas('resources', ['id' => $f3->resource->id]);
    $this->assertDatabaseMissing('resources', ['id' => $f1->resource->id]);
    $this->assertDatabaseMissing('resources', ['id' => $f2->resource->id]);
});

it('doesnt partially delete folders', function () {
    $user = User::factory()->create();

    [$root, $f1, $f2] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Root'], ['name' => 'Folder 1'], ['name' => 'Folder With Children'])
        ->create();

    $f1->resource()->update(['parent_id' => $root->resource->id]);
    $f2->resource()->update(['parent_id' => $f1->resource->id]);

    $video = Video::factory()->recycle($user)->completed()->create();
    $video->resource()->create(['parent_id' => $f2->resource->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson('/v1/folders/resources', ['resources' => [$f1->resource->id, $f2->resource->id]])
        ->assertUnprocessable();

    $this->assertDatabaseHas('folders', ['id' => $f1->id]);
    $this->assertDatabaseHas('folders', ['id' => $f2->id]);

    $this->assertDatabaseHas('resources', ['id' => $f1->resource->id]);
    $this->assertDatabaseHas('resources', ['id' => $f2->resource->id]);
    $this->assertDatabaseHas('resources', ['id' => $video->resource->id]);
});
