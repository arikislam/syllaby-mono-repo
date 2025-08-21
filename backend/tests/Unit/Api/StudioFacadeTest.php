<?php

namespace Tests\Unit\Api;

use App\Syllaby\Videos\Footage;
use Tests\Stubs\CreatomateStub;
use Illuminate\Http\UploadedFile;
use App\Syllaby\Metadata\Timeline;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Support\Facades\Event;
use App\Syllaby\Videos\Vendors\Renders\Studio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can replace the digital twin element source', function () {
    Event::fake();
    Studio::fake('creatomate');

    $source = CreatomateStub::timeline();
    $footage = Footage::factory()->create();
    $clone = RealClone::factory()->for($footage)->create();

    Timeline::factory()->for($footage, 'model')->create();

    $file = UploadedFile::fake()->create('video.txt', 500);
    $clone->addMedia($file)->toMediaCollection('video');

    Studio::assertRealCloneReplaced($source, $clone->getFirstMediaUrl('video'));
});
