<?php

namespace Tests\Feature\Schedulers;

use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

// Placeholder for update method test
it('authenticated user can update occurrence details', function () {
    $this->markTestIncomplete('This test has not been implemented yet.');
});

it('allows users to regenerate a script for a given occurrence', function () {
    $this->markTestIncomplete('This test has not been implemented yet.');
});
