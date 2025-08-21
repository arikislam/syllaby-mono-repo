<?php

namespace Tests\Feature\Auth;

use App\Syllaby\Users\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows successful logout', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('v1/authentication/logout')->assertNoContent();
});
