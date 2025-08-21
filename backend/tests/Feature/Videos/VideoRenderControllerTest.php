<?php

namespace Tests\Feature\Video;

use App\Syllaby\Videos\Video;
use Database\Factories\MediaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can display video by giving a respective media uuid', function () {
    $video = Video::factory()->create();

    $media = MediaFactory::new()->create([
        'model_id' => $video->id,
        'model_type' => $video->getMorphClass(),
        'collection_name' => 'video',
    ]);

    $response = $this->getJson("/v1/videos/$media->uuid/render");
    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($video->id)
        ->and($response->json('data.media.0'))
        ->uuid->toBe($media->uuid)
        ->model_id->toBe($video->id)
        ->model_type->toBe($video->getMorphClass());
});
