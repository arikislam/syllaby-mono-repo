<?php

namespace Tests\Feature\Presets;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Speeches\Voice;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Presets\FacelessPreset;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('lists all presets for the authenticated user', function () {
    $user = User::factory()->create();
    FacelessPreset::factory()->count(3)->for($user)->create();

    $this->actingAs($user);

    $response = $this->getJson('/v1/presets/faceless');
    $response->assertOk();

    $response->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'user_id',
                    'voice_id',
                    'genre_id',
                    'music_id',
                    'background_id',
                    'language',
                    'font_family',
                    'font_color',
                    'position',
                    'duration',
                    'transition',
                    'volume',
                    'sfx',
                    'overlay',
                ],
            ],
        ]);
});

it('fails to fetch presets for unauthenticated user', function () {
    $this->getJson('/v1/presets/faceless')->assertUnauthorized();
});

it('allows user to create a faceless preset', function () {
    $user = User::factory()->create();
    $voice = Voice::factory()->create();
    $music = Media::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
    ]);

    $response = $this->actingAs($user)->postJson('/v1/presets/faceless', [
        'name' => $name = 'Lorem Ipsum Color',
        'voice_id' => $voice->id,
        'music_id' => $music->id,
        'language' => 'english',
        'font_family' => 'lively',
        'genre_id' => $genre->id,
        'font_color' => '#000000',
        'duration' => 400,
        'transition' => 'fade',
        'volume' => 'medium',
        'sfx' => 'none',
        'caption_animation' => 'fade',
        'overlay' => 'rain',
    ])->assertCreated();

    expect($response->json('data'))
        ->name->toBe($name)
        ->user_id->toBe($user->id)
        ->voice_id->toBe($voice->id)
        ->music_id->toBe($music->id)
        ->language->toBe('english')
        ->genre_id->toBe($genre->id)
        ->font_family->toBe('lively')
        ->font_color->toBe('#000000')
        ->duration->toBe(400)
        ->transition->toBe('fade')
        ->volume->toBe('medium')
        ->sfx->toBe('none')
        ->caption_animation->toBe('fade')
        ->overlay->toBe('rain');
});

it('allows user to update a faceless preset', function () {
    $user = User::factory()->create();

    $ancientGenre = Genre::factory()->active()->consistent()->create([
        'name' => 'Ancient Egypt',
        'slug' => StoryGenre::ANCIENT_EGYPT->value,
    ]);

    $preset = FacelessPreset::factory()->for($user)->create([
        'genre_id' => $ancientGenre->id,
    ]);

    $voice = Voice::factory()->create();
    $music = Media::factory()->create();

    $actionGenre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);
    $response = $this->actingAs($user)->patchJson("/v1/presets/faceless/{$preset->id}", [
        'voice_id' => $voice->id,
        'music_id' => $music->id,
        'language' => 'spanish',
        'font_family' => 'elegant',
        'genre_id' => $actionGenre->id,
        'font_color' => '#FFFFFF',
        'position' => 'bottom',
        'duration' => 500,
        'transition' => 'fade',
        'volume' => 'high',
        'sfx' => 'whoosh',
    ])->assertOk();

    expect($response->json('data'))
        ->user_id->toBe($user->id)
        ->voice_id->toBe($voice->id)
        ->music_id->toBe($music->id)
        ->language->toBe('spanish')
        ->font_family->toBe('elegant')
        ->genre_id->toBe($actionGenre->id)
        ->font_color->toBe('#FFFFFF')
        ->position->toBe('bottom')
        ->duration->toBe(500)
        ->transition->toBe('fade')
        ->volume->toBe('high')
        ->sfx->toBe('whoosh');
});

it('prevents user from updating another users faceless preset', function () {
    $user = User::factory()->create();
    $preset = FacelessPreset::factory()->create();

    $testGenre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $this->actingAs($user)->patchJson("/v1/presets/faceless/{$preset->id}", [
        'name' => 'Attempted Update',
        'genre_id' => $testGenre->id,
    ])->assertForbidden();
});

test('it can create preset with watermark settings', function () {
    $user = User::factory()->create();

    $watermark = $user->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $response = $this->actingAs($user)->postJson('/v1/presets/faceless', [
        'name' => 'Watermarked Preset',
        'watermark_id' => $watermark->id,
        'watermark_position' => 'bottom-right',
        'watermark_opacity' => 80,
    ])->assertCreated();

    expect($response->json('data'))
        ->watermark_id->toBe($watermark->id)
        ->watermark_position->toBe('bottom-right')
        ->watermark_opacity->toBe(80);
});

test('it can update preset watermark settings', function () {
    $user = User::factory()->create();

    $preset = FacelessPreset::factory()->for($user)->create([
        'watermark_position' => 'top-left',
        'watermark_opacity' => 50,
    ]);

    $newWatermark = $user->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $response = $this->actingAs($user)->patchJson("/v1/presets/faceless/{$preset->id}", [
        'watermark_id' => $newWatermark->id,
        'watermark_position' => 'bottom-right',
        'watermark_opacity' => 75,
    ])->assertOk();

    expect($response->json('data'))
        ->watermark_id->toBe($newWatermark->id)
        ->watermark_position->toBe('bottom-right')
        ->watermark_opacity->toBe(75);
});

test('it validates watermark belongs to user', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $otherUserWatermark = $otherUser->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $this->actingAs($user)->postJson('/v1/presets/faceless', [
        'name' => 'Invalid Watermark',
        'watermark_id' => $otherUserWatermark->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['watermark_id']);
});

test('it validates watermark position', function () {
    $user = User::factory()->create();

    $watermark = $user->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $this->actingAs($user)->postJson('/v1/presets/faceless', [
        'name' => 'Invalid Position',
        'watermark_id' => $watermark->id,
        'watermark_position' => 'invalid-position',
    ])->assertUnprocessable()->assertJsonValidationErrors(['watermark_position']);
});

test('it validates watermark opacity range', function () {
    $user = User::factory()->create();

    $watermark = $user->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $this->actingAs($user)
        ->postJson('/v1/presets/faceless', [
            'name' => 'Invalid Opacity',
            'watermark_id' => $watermark->id,
            'watermark_opacity' => 101,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['watermark_opacity']);

    $this->actingAs($user)
        ->postJson('/v1/presets/faceless', [
            'name' => 'Invalid Opacity',
            'watermark_id' => $watermark->id,
            'watermark_opacity' => -1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['watermark_opacity']);
});

test('it includes watermark relationship when requested', function () {
    $user = User::factory()->create();

    $watermark = $user->assets()->create([
        'provider' => AssetProvider::CUSTOM,
        'type' => AssetType::WATERMARK,
        'is_private' => true,
        'status' => AssetStatus::SUCCESS,
    ]);

    $preset = FacelessPreset::factory()->for($user)->create([
        'watermark_id' => $watermark->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/v1/presets/faceless?include=watermark')
        ->assertOk();

    expect($response->json('data.0'))
        ->watermark->toBeArray()
        ->watermark->id->toBe($watermark->id);
});

it('can delete presets of a user', function () {
    $user = User::factory()->create();

    $preset = FacelessPreset::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/v1/presets/faceless/{$preset->id}")->assertNoContent();

    $this->assertDatabaseMissing('faceless_presets', ['id' => $preset->id]);
});

it('prevents user from deleting another users faceless preset', function () {
    $user = User::factory()->create();

    $preset = FacelessPreset::factory()->create();

    $this->actingAs($user)->deleteJson("/v1/presets/faceless/{$preset->id}")->assertForbidden();
});

it('can store folder with preset', function () {
    $user = User::factory()->create();

    [$default, $folder] = Folder::factory()
        ->recycle($user)
        ->forEachSequence(['name' => 'Default'], ['name' => 'Test Folder'])
        ->create();

    $folder->resource()->update(['parent_id' => $default->resource->id]);

    $lastGenre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $this->actingAs($user)->postJson('/v1/presets/faceless', [
        'name' => $name = 'Lorem Ipsum Color',
        'resource_id' => $folder->resource->id,
        'language' => 'english',
        'font_family' => 'lively',
        'genre_id' => $lastGenre->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.resource_id', $folder->resource->id);
});
