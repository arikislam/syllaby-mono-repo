<?php

namespace Tests\Feature\Publisher\Publication;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake(MediaHasBeenAddedEvent::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can attach a thumbnail to a publication', function () {
    Storage::fake('spaces');

    $user = User::factory()->create();
    $publication = Publication::factory()->for($user)->create();

    $asset = Asset::factory()->for($user)->withMedia()->create();

    $provider = SocialAccountEnum::Youtube->toString();
    $collection = "{$provider}-thumbnail";

    $this->actingAs($user);
    $this->putJson("v1/publications/{$publication->id}/thumbnails/attach", [
        'asset_id' => $asset->id,
        'provider' => $provider,
    ]);

    $this->assertDatabaseHas('media', [
        'model_id' => $publication->id,
        'collection_name' => $collection,
        'model_type' => Relation::getMorphAlias(Publication::class),
    ]);
});

it('fails to attach a another user thumbnail to a publication', function () {
    Storage::fake('spaces');

    $user = User::factory()->create();
    $publication = Publication::factory()->for($user)->create();

    $asset = Asset::factory()->withMedia()->create();
    $provider = SocialAccountEnum::Youtube->toString();

    $this->actingAs($user);
    $response = $this->putJson("v1/publications/{$publication->id}/thumbnails/attach", [
        'asset_id' => $asset->id,
        'provider' => $provider,
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseMissing('media', [
        'model_id' => $publication->id,
        'model_type' => Relation::getMorphAlias(Publication::class),
    ]);
});
