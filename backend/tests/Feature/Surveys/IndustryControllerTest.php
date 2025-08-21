<?php

namespace App\Feature\Surveys;

use App\Syllaby\Users\User;
use App\Syllaby\Surveys\Industry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('display a list of available industries', function () {
    Industry::factory()->count(4)->create();
    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->getJson('/v1/surveys/industries');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(4);
});

it('display a list of available industries and the user industry if selected', function () {
    $industries = Industry::factory()->count(4)->create();
    $user = User::factory()->hasAttached($industries->last())->create();

    $this->actingAs($user);
    $response = $this->getJson('/v1/surveys/industries');

    $response->assertOk();
    expect($response->json('data'))
        ->toHaveCount(4)
        ->and($response->json())
        ->user_industry->toBe($industries->last()->id);
});
