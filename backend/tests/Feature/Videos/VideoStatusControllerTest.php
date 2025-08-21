<?php

namespace Tests\Feature\Videos;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can display the status for the given video', function () {
    $user = User::factory()->create();
    $video = Video::factory()->for($user)->rendering()->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson("/v1/videos/$video->id/status");

    expect($response->json('data'))
        ->id->toBe($video->id)
        ->status->toBe(VideoStatus::RENDERING->value);
});


