<?php

namespace Tests\Unit\Api\Videos;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Metadata\Caption;
use App\Syllaby\Videos\DTOs\Options;
use App\Syllaby\Videos\Enums\Overlay;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Vendors\Faceless\Builder\AiVideo;

uses(RefreshDatabase::class);

it('adds a sound effect on image transition video source', function () {
    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Comic Book',
        'slug' => StoryGenre::COMIC_BOOK->value,
    ]);

    $faceless = Faceless::factory()->withSource()->for($genre)->create([
        'background_id' => null,
        'estimated_duration' => 60,
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $names = collect($source['elements'][0]['elements'])->pluck('name');
    expect($names)->toContain('sfx');
});

it('omits the sound effect on image transition video source', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $names = collect($source['elements'][0]['elements'])->pluck('name');
    expect($names)->not->toContain('sfx-whoosh');
});

it('adds background music the video source', function () {
    $music = Media::factory()->create([
        'collection_name' => AssetType::AUDIOS->value,
    ]);

    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'music_id' => $music->id,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
        'options' => new Options(
            font_family: 'lively',
            position: 'center',
            aspect_ratio: '9:16',
            sfx: 'whoosh',
            volume: 'low',
        ),
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    expect($source['elements'][0]['elements'][0]['id'])->toContain($music->uuid);
});

it('can add a font color to video source', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $options = new Options(
        font_family: 'lively',
        position: 'center',
        aspect_ratio: '9:16',
        font_color: '#ff12345',
        volume: 'low',
        voiceover: $voiceover->id
    );

    $faceless->update(['options' => $options]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    expect(Arr::get($source, 'elements.2.transcript_color'))->toBe('#ff12345');
});

it('uses the default font color when none is passed', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $options = new Options(
        font_family: 'whimsy',
        position: 'center',
        aspect_ratio: '9:16',
        volume: 'low',
        voiceover: $voiceover->id
    );

    $faceless->update(['options' => $options]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    expect($faceless->options->font_color)->toBe('default')
        ->and(Arr::get($source, 'elements.2.transcript_color'))->toBe('#07ff00');

});

it('adds a transition to the video source', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 1, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $options = new Options(
        font_family: 'lively',
        position: 'center',
        aspect_ratio: '9:16',
        sfx: 'whoosh',
        volume: null,
        transition: 'slide-left',
        voiceover: $voiceover->id
    );

    $faceless->update(['options' => $options]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    expect(Arr::get($source, 'elements.1.animations.0.type'))->toBe('slide');
});

it('adds caption effect to video source', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $options = new Options(
        font_family: 'lively',
        position: 'center',
        aspect_ratio: '9:16',
        sfx: 'whoosh',
        volume: null,
        transition: 'fade',
        voiceover: $voiceover->id,
        caption_effect: CaptionEffect::KARAOKE->value
    );

    $faceless->update(['options' => $options]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $caption = Arr::get($source, 'elements.2');

    expect($caption)->not()->toBeNull()->and($caption['transcript_effect'])->toBe(CaptionEffect::KARAOKE->value);
});

it('adds overlay at the highest track number', function () {
    $music = Media::factory()->create(['collection_name' => AssetType::AUDIOS->value]);

    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'music_id' => $music->id,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
            [
                'words' => [
                    ['text' => 'World', 'start' => 0.3, 'end' => 0.4],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 1, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $options = new Options(
        font_family: 'lively',
        position: 'center',
        aspect_ratio: '9:16',
        sfx: 'whoosh',
        volume: 'medium',
        transition: 'slide-left',
        voiceover: $voiceover->id,
        overlay: Overlay::RAIN->value
    );

    $faceless->update(['options' => $options]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $overlay = collect($source['elements'])->first(fn ($element) => $element['name'] === 'Overlay')['track'];

    expect($overlay)->toBe(4);
});

it('adds watermark to video source', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $watermark = Asset::factory()->withMedia()->create();

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $faceless->update([
        'watermark_id' => $watermark->id,
        'options' => new Options(
            font_family: 'lively',
            position: 'center',
            voiceover: $voiceover->id,
            aspect_ratio: '9:16',
            watermark_position: 'bottom-right',
            watermark_opacity: 50,
        ),
    ]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $watermarkElement = collect($source['elements'])
        ->where('type', 'image')
        ->firstWhere('source', $watermark->getFirstMediaUrl());

    expect($watermarkElement)
        ->type->toBe('image')
        ->x->toBe('100%')
        ->y->toBe('100%')
        ->opacity->toBe('50%')
        ->x_anchor->toBe('100%')
        ->y_anchor->toBe('100%')
        ->source->toBe($watermark->getFirstMediaUrl());
});

it('skips watermark when watermark_id is null', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $voiceover = Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $faceless->update([
        'watermark_id' => null,
        'options' => new Options(
            font_family: 'lively',
            position: 'center',
            aspect_ratio: '9:16',
            voiceover: $voiceover->id,
            watermark_position: 'bottom-right',
            watermark_opacity: 50,
        ),
    ]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $hasCustomWatermark = collect($source['elements'])->contains(
        fn ($element) => $element['type'] === 'image'
            && str_contains($element['name'] ?? '', 'watermark')
            && $element['name'] !== 'syllaby-watermark'
    );

    expect($hasCustomWatermark)->toBeFalse();
});

it('skips watermark when position is none', function () {
    $faceless = Faceless::factory()->withSource()->create([
        'background_id' => null,
        'estimated_duration' => 60,
        'genre_id' => Genre::factory()->active()->consistent()->create([
            'name' => 'Comic Book',
            'slug' => StoryGenre::COMIC_BOOK->value,
        ])->id,
    ]);

    $captions = Caption::factory()->for($faceless, 'model')->create([
        'user_id' => $faceless->user_id,
        'content' => [
            [
                'words' => [
                    ['text' => 'Hello', 'start' => 0.1, 'end' => 0.2],
                ],
            ],
        ],
    ]);

    Asset::factory()->withMedia()->hasAttached(
        $faceless, ['order' => 0, 'active' => true], 'videos'
    )->create(['status' => AssetStatus::SUCCESS]);

    $faceless->update([
        'watermark_id' => null,
        'options' => new Options(
            font_family: 'lively',
            position: 'center',
            aspect_ratio: '9:16',
            watermark_position: 'none',
            watermark_opacity: 50,
        ),
    ]);

    $source = (new AiVideo($faceless, $captions->content))->build();

    $hasCustomWatermark = collect($source['elements'])->contains(
        fn ($element) => $element['type'] === 'image'
            && str_contains($element['name'] ?? '', 'watermark')
            && $element['name'] !== 'syllaby-watermark'
    );

    expect($hasCustomWatermark)->toBeFalse();
});
