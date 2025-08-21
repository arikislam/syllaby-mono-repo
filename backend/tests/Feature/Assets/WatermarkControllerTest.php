<?php

use Tests\TestCase;
use App\Syllaby\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Assets\Enums\AssetType;
use App\Syllaby\Assets\Enums\AssetStatus;
use App\Syllaby\Assets\Enums\AssetProvider;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake(PerformConversionsJob::class);
    Event::fake(MediaHasBeenAddedEvent::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

test('it requires authentication', function () {
    $this->postJson('/v1/assets/watermark', [
        'files' => [
            UploadedFile::fake()->image('logo.png', 100, 100),
        ],
    ])->assertUnauthorized();
});

test('it requires subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withMiddleware(PaidCustomersMiddleware::class)->postJson('/v1/assets/watermark', [
        'files' => [
            UploadedFile::fake()->image('logo.png', 100, 100),
        ],
    ])->assertPaymentRequired();
});

test('it validates file is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/v1/assets/watermark', [])
        ->assertForbidden()
        ->assertJsonPath('message', 'A file is required');
});

test('it validates file is image', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/assets/watermark', [
        'files' => [
            UploadedFile::fake()->create('document.pdf', 200),
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('files.0');
});

test('it validates image dimensions', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/assets/watermark', [
        'files' => [
            UploadedFile::fake()->image('logo.png', 200, 200)->size(200),
        ],
    ])->assertUnprocessable();
});

test('it validates file size', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/assets/watermark', [
        'files' => [
            UploadedFile::fake()->image('logo.png')->size(2000), // 2MB
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['files.0']);
});

test('it can upload watermark', function () {
    Feature::define('max_storage', TestCase::BASE_MAX_ALLOWED_STORAGE);

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('logo.png', 100, 100)->size(200);

    $this->actingAs($user)->postJson('/v1/assets/watermark', [
        'files' => [$file],
    ])->assertSuccessful();

    $asset = $user->assets()->latest()->first();

    expect($asset)
        ->user_id->toBe($user->id)
        ->provider->toBe(AssetProvider::CUSTOM)
        ->type->toBe(AssetType::WATERMARK)
        ->is_private->toBe(1)
        ->status->toBe(AssetStatus::SUCCESS)
        ->and($asset->media->first())->not->toBeNull();
});
