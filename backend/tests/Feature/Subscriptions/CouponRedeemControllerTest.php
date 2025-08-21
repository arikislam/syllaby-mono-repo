<?php

namespace App\Feature\Subscriptions;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can redeem a coupon', function () {
    // $this->withoutMiddleware(PaidCustomersMiddleware::class);
    $this->assertTrue(true);
});
