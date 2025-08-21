<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Metadata\Caption;
use Illuminate\Support\Facades\Queue;
use App\Syllaby\Videos\Enums\StoryGenre;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('exports a faceless video with edited captions', function () {
    Feature::define('video', true);

    Queue::fake();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->recycle($user)->create();

    $caption = Caption::factory()->for($faceless, 'model')->for($user)->create([
        'user_id' => $user->id,
        'content' => [
            [
                'text' => 'Hello world Foo',
                'start' => 0,
                'end' => 1,
                'words' => [
                    ['text' => 'Hello', 'start' => 0, 'end' => 1],
                    ['text' => 'world', 'start' => 1, 'end' => 2],
                    ['text' => 'Foo', 'start' => 2, 'end' => 3],
                ],
            ],
            [
                'text' => 'Bar Baz',
                'start' => 0,
                'end' => 1,
                'words' => [
                    ['text' => 'Bar', 'start' => 0, 'end' => 1],
                    ['text' => 'Baz', 'start' => 1, 'end' => 2],
                ],
            ],
            [
                'text' => 'Woo Foo',
                'start' => 0,
                'end' => 1,
                'words' => [
                    ['text' => 'Woo', 'start' => 0, 'end' => 1],
                    ['text' => 'Foo', 'start' => 1, 'end' => 2],
                ],
            ],
        ],
    ]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/export", [
        'transcriptions' => [
            '0' => ['goodbye', 'world', 'yolo'],
            '2' => ['woos', 'Foo'],
        ],
    ])->assertAccepted();

    $caption->refresh();

    expect($caption->content)->toHaveCount(3)->sequence(
        fn ($caption) => expect($caption->value['text'])->toBe('goodbye world yolo'),
        fn ($caption) => expect($caption->value['text'])->toBe('Bar Baz'),
        fn ($caption) => expect($caption->value['text'])->toBe('woos Foo'),
    );
});

it('exports a faceless video with selected assets', function () {
    Queue::fake();
    Feature::define('video', true);

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Hyper Realism',
        'slug' => StoryGenre::HYPER_REALISM->value,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create(['genre_id' => $genre->id]);

    [$asset1, $asset2] = Asset::factory(2)->recycle($user)->withMedia()->create();

    $faceless->assets()->attach($asset1->id, ['order' => 1, 'active' => true]);
    $faceless->assets()->attach($asset2->id, ['order' => 2, 'active' => true]);

    // Simulate the assets that will be selected by the user
    [$final1, $final2] = Asset::factory(2)->recycle($user)->withMedia()->create();

    $faceless->assets()->attach($final1->id, ['order' => 1, 'active' => true]);
    $faceless->assets()->updateExistingPivot($asset1->id, ['active' => false]);

    $faceless->assets()->attach($final2->id, ['order' => 2, 'active' => true]);
    $faceless->assets()->updateExistingPivot($asset2->id, ['active' => false]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/export")->assertAccepted();

    $faceless->refresh();

    expect($faceless->assets)->toHaveCount(4)->sequence(
        fn ($asset) => expect($asset->value->pivot)->order->toBe(1)->active->toBeFalse(),
        fn ($asset) => expect($asset->value->pivot)->order->toBe(1)->active->toBeTrue(),
        fn ($asset) => expect($asset->value->pivot)->order->toBe(2)->active->toBeFalse(),
        fn ($asset) => expect($asset->value->pivot)->order->toBe(2)->active->toBeTrue(),
    );

    Queue::assertPushed(BuildFacelessVideoSource::class);
});

it('exports a faceless video without watermark and music and charges the user', function () {
    Feature::define('video', true);

    Queue::fake();

    $user = User::factory()->create();

    $music = Asset::factory()->audio()->create();
    $media = Media::factory()->for($music, 'model')->create();

    $faceless = Faceless::factory()->recycle($user)
        ->for(Asset::factory()->watermark()->create(), 'watermark')
        ->for($media, 'music')
        ->create();

    Media::factory()->for($faceless, 'model')->recycle($user)->create([
        'name' => 'voiceover',
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 120],
    ]);

    $this->actingAs($user)->postJson("/v1/videos/faceless/{$faceless->id}/export", [
        'watermark' => [
            'id' => null,
            'opacity' => null,
            'position' => null,
        ],
        'music_id' => null,
    ])
        ->assertAccepted()
        ->assertJsonPath('data.watermark_id', null)
        ->assertJsonPath('data.music_id', null);

    $this->assertDatabaseHas(Faceless::class, [
        'id' => $faceless->id,
        'watermark_id' => null,
        'music_id' => null,
    ]);

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'remaining_credit_amount' => 497,
    ]);
});
