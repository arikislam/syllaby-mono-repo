<?php

namespace Tests\Unit\Credits;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Subscriptions\Plan;
use Illuminate\Support\Facades\Cache;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Credits\Actions\ChargeFacelessVideoAction;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
});

it('charges a faceless video', function () {
    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 500,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create();
    Media::factory()->for($faceless, 'model')->create([
        'collection_name' => 'script',
        'mime_type' => 'audio/mpeg',
        'custom_properties' => ['duration' => 60],
    ]);

    app(ChargeFacelessVideoAction::class)->handle($faceless, $user);

    expect($user->fresh()->remaining_credit_amount)->toBe(487);
});

it('refunds a faceless video', function () {
    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 500,
    ]);

    $faceless = Faceless::factory()->recycle($user)->create();
    Media::factory()->for($faceless, 'model')->create([
        'user_id' => $user->id,
        'mime_type' => 'audio/mpeg',
        'collection_name' => 'script',
        'custom_properties' => ['duration' => 60],
    ]);

    app(ChargeFacelessVideoAction::class)->handle($faceless, $user);
    expect($user->fresh()->remaining_credit_amount)->toBe(487);

    (new CreditService($user))->refund($faceless, CreditEventEnum::FACELESS_VIDEO_GENERATED);
    expect($user->fresh()->remaining_credit_amount)->toBe(500);
});