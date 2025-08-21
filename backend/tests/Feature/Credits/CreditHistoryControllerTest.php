<?php

namespace Tests\Feature\Credits;

use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Credits\CreditHistory;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can fetch credit history of a user', function () {
    $user = User::factory()->create();

    CreditHistory::factory()->for($user)->count(10)->create();

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/credit-history');

    $response->assertOk()->assertJsonStructure([
        'data' => [
            '*' => [
                'created_at',
                'label',
                'content_type',
                'credit_spend',
                'transaction_type',
            ],
        ],
    ]);
});

it('records the credit transactions for the different actions', function () {
    $user = User::factory()->create();
    $service = new CreditService($user);

    $video = Video::factory()->for($user)->create();
    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);

    $speech = Speech::factory()->for($user)->create();
    $service->decrement(CreditEventEnum::TEXT_TO_SPEECH_GENERATED, $speech, 7, [], 'Hello World.');

    $clone = RealClone::factory()->for($user)->create();
    $service->decrement(CreditEventEnum::REAL_CLONE_GENERATED, $clone, 25, [], 'Real Clone');

    $service->decrement(CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID, null, 100, [], 'Subscription Renewed');

    $this->actingAs($user, 'sanctum');
    $response = $this->getJson('v1/user/credit-history');

    $response->assertJsonCount(4, 'data');
});

it('prevents same item to be double refunded', function () {
    $user = User::factory()->create([
        'remaining_credit_amount' => 500,
    ]);
    $service = new CreditService($user);

    $video = Video::factory()->for($user)->create();
    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);

    $service->refund($video);
    $service->refund($video);

    expect($user->remaining_credit_amount)->toBe(500);
    $this->assertDatabaseCount('credit_histories', 2);
});

it('allows same item to be refunded multiple times if new charges occurs', function () {
    $user = User::factory()->create([
        'remaining_credit_amount' => 500,
    ]);
    $service = new CreditService($user);

    $video = Video::factory()->for($user)->create();

    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);
    $service->refund($video);

    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);
    $service->refund($video);

    expect($user->remaining_credit_amount)->toBe(500);
    $this->assertDatabaseCount('credit_histories', 4);
});

it('allows an item to be charged then refunded and charged again', function () {
    $user = User::factory()->create([
        'remaining_credit_amount' => 500,
    ]);
    $service = new CreditService($user);

    $video = Video::factory()->for($user)->create();

    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);
    $service->refund($video);

    $service->decrement(CreditEventEnum::VIDEO_GENERATED, $video, 30, [], $video->title);

    expect($user->remaining_credit_amount)->toBe(470);
    $this->assertDatabaseCount('credit_histories', 3);
});
