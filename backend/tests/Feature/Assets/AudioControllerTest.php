<?php

namespace Tests\Feature\Assets;

use Tests\TestCase;
use App\Syllaby\Tags\Tag;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use App\Syllaby\Assets\Enums\AssetType;
use Illuminate\Support\Facades\Storage;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake(PerformConversionsJob::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('display a list of only available stock audios', function () {
    Storage::fake('spaces');

    $user = User::factory()->create();

    $asset = Asset::factory()->global()->create(['type' => AssetType::AUDIOS->value]);

    Media::factory(4)->for($asset, 'model')->create(['collection_name' => AssetType::AUDIOS->value]);

    $response = $this->actingAs($user)->getJson('/v1/assets/audios');

    $response->assertOk();

    $response->assertJsonCount(4, 'data');
});

it('display a list available stock and user uploaded audios', function () {
    Storage::fake('spaces');

    $user = User::factory()->create();

    $stock = Asset::factory()->global()->count(2)->create(['type' => AssetType::AUDIOS->value]);
    $stock->each(fn (Asset $asset) => Media::factory()->for($asset, 'model')->create([
        'collection_name' => AssetType::AUDIOS->value,
        'custom_properties' => ['is_stock' => true],
    ]));

    $uploaded = Asset::factory()->count(3)->for($user)->create(['type' => AssetType::AUDIOS->value]);
    $uploaded->each(fn (Asset $asset) => Media::factory()->for($asset, 'model')->create([
        'collection_name' => AssetType::AUDIOS->value,
        'custom_properties' => ['is_stock' => false],
    ]));

    $this->actingAs($user);
    $response = $this->getJson('/v1/assets/audios');

    $response->assertOk();

    $response->assertJsonCount(5, 'data');
});

it('upload an audio file to the user global assets and associates it to the uploads tag', function () {
    Storage::fake('spaces');
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/assets/audios', [
        'files' => [UploadedFile::fake()->create('audio.mp3', 40, 'audio/mpeg')],
    ]);

    $response->assertOk();
    $media = Media::find($response->json('data.0.id'));
    Storage::disk('spaces')->assertExists($media->getPathRelativeToRoot());

    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'slug' => "user-{$user->id}-audio-uploads",
    ]);

    $this->assertDatabaseHas('taggables', [
        'taggable_id' => $media->id,
        'taggable_type' => Relation::getMorphAlias(Media::class),
    ]);
});

it('fetches only audio files with a given tag', function () {
    Storage::fake('spaces');

    $user = User::factory()->create();
    $asset = Asset::factory()->for($user)->create(['type' => AssetType::AUDIOS->value]);

    $music = Tag::factory()->create(['slug' => 'music']);
    $video = Tag::factory()->create(['slug' => 'video']);
    $sfx = Tag::factory()->create(['slug' => 'sfx']);

    $audio1 = Media::factory()->for($asset, 'model')->create([
        'name' => 'audio1',
        'collection_name' => AssetType::AUDIOS->value,
    ]);
    $audio1->tags()->attach($music);

    $audio2 = Media::factory()->for($asset, 'model')->create([
        'name' => 'audio2',
        'collection_name' => AssetType::AUDIOS->value,
    ]);
    $audio2->tags()->attach([$music->id, $video->id]);

    $audio3 = Media::factory()->for($asset, 'model')->create([
        'name' => 'audio3',
        'collection_name' => AssetType::AUDIOS->value,
    ]);
    $audio3->tags()->attach([$sfx->id]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/assets/audios?filter[tag]=music');

    $response->assertOk();

    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $audio2->id);
    $response->assertJsonPath('data.1.id', $audio1->id);
});

it('fails to upload a file other than audio', function () {
    Storage::fake('spaces');
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->postJson('/v1/assets/audios', [
        'files' => [UploadedFile::fake()->create('video.mp4', 4000, 'video/mp4')],
    ]);

    $response->assertUnprocessable();
});
