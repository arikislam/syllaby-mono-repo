<?php

namespace Tests\Feature\Ideas;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Idea;
use Illuminate\Support\Arr;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Surveys\Industry;
use App\Syllaby\Users\Enums\UserType;
use Database\Seeders\SystemUserSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('doesnt shows idea suggestions from users searches', function () {
    $user = User::factory()->create();

    $legal = Industry::factory()->create(['name' => 'Legal', 'slug' => 'legal']);

    Keyword::factory()
        ->hasAttached(User::factory()->count(4)->hasAttached($legal))
        ->has(Idea::factory()->count(2))
        ->create();

    $query = Arr::query(['filter' => ['industry' => 'legal'],]);

    $this->actingAs($user)->getJson(sprintf("/v1/ideas/suggestions?%s", $query))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('links.next', null);
});

it('only shows idea suggestions from system admin', function () {
    $user = User::factory()->create();

    $legal = Industry::factory()->create(['name' => 'Legal', 'slug' => 'legal']);

    $this->seed(SystemUserSeeder::class);

    Keyword::factory()
        ->hasAttached(User::factory()->hasAttached($legal), ['audience' => 'legal'])
        ->has(Idea::factory())
        ->create();

    /** @var Keyword $k2 */
    $k2 = Keyword::factory()
        ->hasAttached(User::firstWhere('user_type', UserType::ADMIN), ['audience' => 'legal'])
        ->has(Idea::factory()->public())
        ->create();

    $query = Arr::query(['filter' => ['industry' => 'legal']]);

    $this->actingAs($user)->getJson(sprintf("/v1/ideas/suggestions?%s", $query))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.keyword_id', $k2->id)
        ->assertJsonPath('data.0.id', $k2->ideas()->first()->id);
});

it('only shows idea suggestions from selected industry', function () {
    $user = User::factory()->create();

    $this->seed(SystemUserSeeder::class);

    Keyword::factory()
        ->hasAttached(User::firstWhere('user_type', UserType::ADMIN), ['audience' => 'legal'])
        ->has(Idea::factory())
        ->create();

    /** @var Keyword $k2 */
    $k2 = Keyword::factory()
        ->hasAttached(User::firstWhere('user_type', UserType::ADMIN), ['audience' => 'health'])
        ->has(Idea::factory()->public())
        ->create();

    $query = Arr::query(['filter' => ['industry' => 'health']]);

    $this->actingAs($user)->getJson(sprintf("/v1/ideas/suggestions?%s", $query))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.keyword_id', $k2->id)
        ->assertJsonPath('data.0.id', $k2->ideas()->first()->id);
});