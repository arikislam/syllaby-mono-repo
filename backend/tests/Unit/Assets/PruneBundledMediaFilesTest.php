<?php

namespace Tests\Unit\Assets;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('spaces');
    Carbon::setTestNow('2024-01-01 10:00:00');
});

it('prunes media bundles older than 1 hour', function () {
    $oldTimestamp = now()->subHours(2)->timestamp;
    Storage::disk('spaces')->makeDirectory("tmp/zips/{$oldTimestamp}");

    $recentTimestamp = now()->subMinutes(30)->timestamp;
    Storage::disk('spaces')->makeDirectory("tmp/zips/{$recentTimestamp}");

    $this->artisan('syllaby:prune-media-bundles');

    Storage::disk('spaces')->assertMissing("tmp/zips/{$oldTimestamp}");

    Storage::disk('spaces')->assertExists("tmp/zips/{$recentTimestamp}");
});

it('handles empty tmp/zips directory', function () {
    $this->artisan('syllaby:prune-media-bundles')->assertSuccessful();
});
