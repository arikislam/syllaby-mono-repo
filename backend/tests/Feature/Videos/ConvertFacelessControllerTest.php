<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Footage;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Metadata\Timeline;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\VideoProvider;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Actions\ConvertFacelessAction;
use Illuminate\Database\Eloquent\Relations\Relation;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can convert a rendered faceless video to footage', function () {
    Str::createUuidsUsing(fn () => new LazyUuidFromString('uuid'));

    $user = User::factory()->withDefaultFolder()->create();

    /** @var Video $video */
    $video = Video::factory()->faceless()->completed()->for($user)
        ->has(Faceless::factory()->for($user))
        ->create();

    Media::factory()->create([
        'model_id' => $video->id,
        'model_type' => $video->getMorphClass(),
        'collection_name' => 'video',
    ]);

    $response = $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$video->faceless->id}/convert")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'user_id', 'title', 'provider', 'provider_id', 'type', 'url', 'status', 'synced_at', 'footage',
            ],
        ]);

    $expectedSource = app(ConvertFacelessAction::class)->defaultSource($video->getFirstMedia('video'), $video->faceless);

    $this->assertDatabaseHas(Video::class, [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
        'title' => $video->title,
        'type' => Video::CUSTOM,
        'status' => VideoStatus::DRAFT,
        'provider' => VideoProvider::CREATOMATE,
        'provider_id' => null,
        'url' => null,
        'synced_at' => null,
    ]);

    $this->assertDatabaseHas(Footage::class, [
        'user_id' => $user->id,
        'video_id' => $response->json('data.id'),
    ]);

    $this->assertDatabaseHas(Timeline::class, [
        'model_id' => $response->json('data.footage.id'),
        'model_type' => Relation::getMorphAlias(Footage::class),
        'hash' => (new Timeline)->rehash($expectedSource),
    ]);

    Str::createUuidsNormally();
});

it('fails to convert a non rendered faceless video to footage', function () {
    $user = User::factory()->create();

    /** @var Video $video */
    $video = Video::factory()->faceless()->rendering()->for($user)
        ->has(Faceless::factory()->for($user))
        ->create();

    $this->actingAs($user)
        ->postJson("v1/videos/faceless/{$video->faceless->id}/convert")
        ->assertBadRequest();
});
