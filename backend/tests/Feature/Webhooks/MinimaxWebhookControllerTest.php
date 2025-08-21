<?php

namespace Tests\Feature\Webhooks;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Credits\CreditEvent;
use Illuminate\Support\Facades\Queue;
use App\Syllaby\Credits\CreditHistory;
use App\Syllaby\Assets\Enums\AssetStatus;
use Database\Seeders\CreditEventTableSeeder;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Animation\Jobs\DownloadAnimation;
use App\Syllaby\Credits\Enums\CreditEventTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('webhook handles challenge request', function () {
    $this->postJson('/minimax/webhook', ['challenge' => 'test-challenge'])
        ->assertOk()
        ->assertJson(['challenge' => 'test-challenge']);
});

test('webhook handles missing asset gracefully', function () {
    $faceless = Faceless::factory()->create();

    $response = $this->postJson('/minimax/webhook', [
        'status' => 'success',
        'task_id' => 'non-existent-id',
        'faceless_id' => $faceless->id,
    ]);

    expect($response->status())->toBe(200);
});

test('webhook handles missing faceless gracefully', function () {
    Queue::fake();
    $user = User::factory()->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'status' => AssetStatus::PROCESSING,
        'provider_id' => '123456',
    ]);

    $response = $this->postJson('/minimax/webhook', [
        'status' => 'success',
        'task_id' => $asset->provider_id,
        'faceless_id' => 999999,
        'file_id' => 12345678,
        'base_resp' => ['status_code' => 0],
    ]);

    expect($response->status())->toBe(200);
    Queue::assertPushed(DownloadAnimation::class);
});

test('webhook handles failed animation generation', function () {
    Queue::fake();
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 1026],
        'status' => 'failed',
    ])->assertOk();

    $asset->refresh();

    expect($asset->status)->toBe(AssetStatus::FAILED);
    Queue::assertNotPushed(DownloadAnimation::class);
});

test('webhook handles successful animation generation', function () {
    Queue::fake();

    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 0],
        'status' => 'success',
        'file_id' => 12345678,
    ])->assertOk();

    Queue::assertPushed(DownloadAnimation::class, function ($job) use ($faceless, $asset) {
        return $job->asset->id === $asset->id
            && $job->faceless->id === $faceless->id
            && $job->identifier === 12345678;
    });
});

test('webhook handles processing status update', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 0],
        'status' => 'processing',
    ])->assertOk();

    $asset->refresh();

    expect($asset->status)->toBe(AssetStatus::PROCESSING);
});

test('webhook handles queueing status update', function () {
    $user = User::factory()->create();

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 0],
        'status' => 'queueing',
    ])->assertOk();

    $asset->refresh();

    expect($asset->status)->toBe(AssetStatus::PROCESSING);
});

test('webhook refunds credits on failed animation', function () {
    $this->seed(CreditEventTableSeeder::class);

    $user = User::factory()->create([
        'monthly_credit_amount' => 500,
        'remaining_credit_amount' => 490, // 10 credits spent
    ]);

    $faceless = Faceless::factory()->for($user)->create();

    $asset = Asset::factory()->recycle($user)->aiVideo()->create([
        'provider_id' => '123456',
        'status' => AssetStatus::PROCESSING,
    ]);

    CreditHistory::factory()->recycle($user)->create([
        'credit_events_id' => CreditEvent::where('name', CreditEventEnum::IMAGE_ANIMATED)->first()->id,
        'creditable_id' => $faceless->id,
        'creditable_type' => $faceless->getMorphClass(),
        'amount' => 10,
        'event_type' => CreditEventTypeEnum::SPEND,
        'description' => CreditEventEnum::IMAGE_ANIMATED->value,
    ]);

    $this->postJson('/minimax/webhook', [
        'task_id' => $asset->provider_id,
        'faceless_id' => $faceless->id,
        'base_resp' => ['status_code' => 1026],
        'status' => 'failed',
    ])->assertOk();

    $user->refresh();
    $asset->refresh();

    expect($asset->status)->toBe(AssetStatus::FAILED)
        ->and($user->remaining_credit_amount)->toBe(500) // Credits refunded
        ->and(CreditHistory::count())->toBe(2); // Original spend + refund record
});
