<?php

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Metadata\Caption;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Videos\Jobs\Faceless\ManageFacelessAssets;

uses(RefreshDatabase::class);

it('stores selected assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    [$a1, $a2, $a3] = Asset::factory(3)->for($user)->success()->create();

    $selected = [
        ['id' => $a1->id, 'order' => 2],
        ['id' => $a2->id, 'order' => 0],
        ['id' => $a3->id, 'order' => 1],
    ];

    (new ManageFacelessAssets($faceless, $selected))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(3)
        ->and($stored[0]->id)->toBe($a2->id) // order 0
        ->and($stored[1]->id)->toBe($a3->id) // order 1
        ->and($stored[2]->id)->toBe($a1->id); // order 2
});

it('repeats assets when fewer than captions', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [
            ['text' => 'Segment 1', 'start' => 0, 'end' => 1],
            ['text' => 'Segment 2', 'start' => 1, 'end' => 2],
            ['text' => 'Segment 3', 'start' => 2, 'end' => 3],
            ['text' => 'Segment 4', 'start' => 3, 'end' => 4],
            ['text' => 'Segment 5', 'start' => 4, 'end' => 5],
        ],
    ]);

    [$a1, $a2] = Asset::factory(2)->for($user)->success()->create();

    $faceless->assets()->attach($a1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($a2, ['order' => 1, 'active' => true]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(5)
        ->and($stored[0]->id)->toBe($a1->id)
        ->and($stored[1]->id)->toBe($a2->id)
        ->and($stored[2]->id)->toBe($a1->id)
        ->and($stored[3]->id)->toBe($a2->id)
        ->and($stored[4]->id)->toBe($a1->id);
});

it('does nothing when no captions exist', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    [$a1, $a2] = Asset::factory(2)->for($user)->success()->create();

    $faceless->assets()->attach($a1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($a2, ['order' => 1, 'active' => true]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(2)
        ->and($stored[0]->id)->toBe($a1->id)
        ->and($stored[1]->id)->toBe($a2->id);
});

it('does nothing when no assets exist', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [
            ['text' => 'Segment 1', 'start' => 0, 'end' => 1],
            ['text' => 'Segment 2', 'start' => 1, 'end' => 2],
        ],
    ]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->get();

    expect($stored)->toHaveCount(0);
});

it('does nothing when enough assets already exist', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [
            ['text' => 'Segment 1', 'start' => 0, 'end' => 1],
            ['text' => 'Segment 2', 'start' => 1, 'end' => 2],
            ['text' => 'Segment 3', 'start' => 2, 'end' => 3],
        ],
    ]);

    [$a1, $a2, $a3] = Asset::factory(3)->for($user)->success()->create();

    $faceless->assets()->attach($a1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($a2, ['order' => 1, 'active' => true]);
    $faceless->assets()->attach($a3, ['order' => 2, 'active' => true]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(3)
        ->and($stored[0]->id)->toBe($a1->id)
        ->and($stored[1]->id)->toBe($a2->id)
        ->and($stored[2]->id)->toBe($a3->id);
});

it('handles empty captions content', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [],
    ]);

    [$a1, $a2] = Asset::factory(2)->for($user)->success()->create();

    $faceless->assets()->attach($a1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($a2, ['order' => 1, 'active' => true]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(2);
});

it('ignores inactive or failed assets', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [
            ['text' => 'Segment 1', 'start' => 0, 'end' => 1],
            ['text' => 'Segment 2', 'start' => 1, 'end' => 2],
            ['text' => 'Segment 3', 'start' => 2, 'end' => 3],
        ],
    ]);

    $assets = [
        Asset::factory()->for($user)->create(['status' => AssetStatus::SUCCESS]),
        Asset::factory()->for($user)->create(['status' => AssetStatus::SUCCESS]),
        Asset::factory()->for($user)->create(['status' => AssetStatus::FAILED]),
        Asset::factory()->for($user)->create(['status' => AssetStatus::SUCCESS]),
    ];

    $faceless->assets()->attach($assets[0], ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($assets[1], ['order' => 1, 'active' => true]);
    $faceless->assets()->attach($assets[2], ['order' => 2, 'active' => true]);
    $faceless->assets()->attach($assets[3], ['order' => 3, 'active' => false]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->where('active', true)->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(3)
        ->and($stored[0]->id)->toBe($assets[0]->id)
        ->and($stored[1]->id)->toBe($assets[1]->id)
        ->and($stored[2]->id)->toBe($assets[0]->id);
});

it('can use other strategies for asset repetition', function () {
    config()->set('videos.asset_repetition_strategy', 'first');

    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    Caption::factory()->for($faceless, 'model')->create([
        'content' => [
            ['text' => 'Segment 1', 'start' => 0, 'end' => 1],
            ['text' => 'Segment 2', 'start' => 1, 'end' => 2],
            ['text' => 'Segment 3', 'start' => 2, 'end' => 3],
            ['text' => 'Segment 4', 'start' => 3, 'end' => 4],
            ['text' => 'Segment 5', 'start' => 4, 'end' => 5],
        ],
    ]);

    [$a1, $a2] = Asset::factory(2)->for($user)->success()->create();

    $faceless->assets()->attach($a1, ['order' => 0, 'active' => true]);
    $faceless->assets()->attach($a2, ['order' => 1, 'active' => true]);

    (new ManageFacelessAssets($faceless, []))->handle();

    $stored = $faceless->assets()->orderBy('video_assets.order')->get();

    expect($stored)->toHaveCount(5)
        ->and($stored[0]->id)->toBe($a1->id)
        ->and($stored[1]->id)->toBe($a2->id)
        ->and($stored[2]->id)->toBe($a1->id)
        ->and($stored[3]->id)->toBe($a1->id)
        ->and($stored[4]->id)->toBe($a1->id);
});
