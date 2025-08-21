<?php

namespace Tests\Feature\Schedulers;

use App\Syllaby\Users\User;
use Illuminate\Http\UploadedFile;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('parses uploaded CSV file correctly with comma delimiter', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'bulk_video_template_comma.csv',
        content: file_get_contents(base_path('tests/Stubs/BulkScheduler/bulk_video_template_comma.csv'))
    );

    $this->actingAs($user);
    $response = $this->postJson('v1/schedulers/csv-parser', ['file' => $file]);

    $response->assertOk();

    $response->assertJsonFragment(['data' => [
        [
            'title' => 'Budget Travel Tips',
            'caption' => null,
            'script' => 'Are you dreaming of traveling the world but worried about your budget? In today\'s video, I\'ll share proven strategies that helped me travel to multiple countries on less than $50 a day.',
        ],
        [
            'title' => 'Hidden Travel Gems',
            'caption' => null,
            'script' => 'Today I\'m revealing hidden gems that most tourists never discover. These secret spots will give you authentic experiences without the crowds and high prices.',
        ],
        [
            'title' => 'Travel Hacks',
            'caption' => null,
            'script' => 'Welcome back! I\'m sharing my top secret travel hacks that airlines don\'t want you to know. These tips will save you hundreds of dollars on your next trip.',
        ],
    ]]);
});

it('parses uploaded CSV file correctly with semicolon delimiter', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'bulk_video_template_semicolon.csv',
        content: file_get_contents(base_path('tests/Stubs/BulkScheduler/bulk_video_template_semicolon.csv'))
    );

    $this->actingAs($user);
    $response = $this->postJson('v1/schedulers/csv-parser', ['file' => $file]);

    $response->assertOk();

    $response->assertJsonFragment(['data' => [
        [
            'title' => 'Budget Travel Tips',
            'caption' => null,
            'script' => 'Are you dreaming of traveling the world but worried about your budget? In today\'s video, I\'ll share proven strategies that helped me travel to multiple countries on less than $50 a day.',
        ],
        [
            'title' => 'Hidden Travel Gems',
            'caption' => null,
            'script' => 'Today I\'m revealing hidden gems that most tourists never discover. These secret spots will give you authentic experiences without the crowds and high prices.',
        ],
        [
            'title' => 'Travel Hacks',
            'caption' => null,
            'script' => 'Welcome back! I\'m sharing my top secret travel hacks that airlines don\'t want you to know. These tips will save you hundreds of dollars on your next trip.',
        ],
    ]]);
});

it('validates CSV file is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->postJson('v1/schedulers/csv-parser', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['file']);
});

it('validates CSV file type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.txt', 100);

    $response = $this->postJson('v1/schedulers/csv-parser', [
        'file' => $file,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['file']);
});
it('validates maximum number of CSV rows', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'max_rows_file.csv',
        content: file_get_contents(base_path('tests/Stubs/BulkScheduler/max_rows_file.csv'))
    );

    $this->actingAs($user);
    $response = $this->postJson('v1/schedulers/csv-parser', ['file' => $file]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['file']);
});
