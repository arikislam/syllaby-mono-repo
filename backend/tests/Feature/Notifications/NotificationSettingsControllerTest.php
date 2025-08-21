<?php

use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can enable the mail notification preferences of the user', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'notifications' => [
            'real_clones' => ['mail' => false, 'database' => true],
            'publications' => ['mail' => true, 'database' => true],
            'scheduler' => [
                'reminders' => ['mail' => true, 'database' => true],
                'generated' => ['mail' => false, 'database' => true],
            ],
        ],
    ]);

    $response = $this->actingAs($user)->putJson('v1/notifications/settings', [
        'real_clones' => ['mail' => true],
        'scheduler' => [
            'reminders' => ['mail' => true],
            'generated' => ['mail' => true],
        ],
    ]);

    $response->assertOk();

    $response->assertJsonFragment([
        'message' => 'Notifications preferences updated.',
    ]);

    $user->refresh();

    $this->assertEquals([
        'real_clones' => ['mail' => true, 'database' => true],
        'publications' => ['mail' => true, 'database' => true],
        'scheduler' => [
            'reminders' => ['mail' => true, 'database' => true],
            'generated' => ['mail' => true, 'database' => true],
        ],
    ], $user->notifications);
});

it('can disable the mail notification preference of the user', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'notifications' => [
            'real_clones' => ['mail' => true, 'database' => true],
            'publications' => ['mail' => true, 'database' => true],
            'scheduler' => [
                'reminders' => ['mail' => true, 'database' => true],
                'generated' => ['mail' => false, 'database' => true],
            ],
        ],
    ]);

    $response = $this->actingAs($user)->putJson('v1/notifications/settings', [
        'real_clones' => ['mail' => false],
    ]);

    $response->assertOk();

    $response->assertJsonFragment([
        'message' => 'Notifications preferences updated.',
    ]);

    $user->refresh();

    $this->assertEquals([
        'real_clones' => ['mail' => false, 'database' => true],
        'publications' => ['mail' => true, 'database' => true],
        'scheduler' => [
            'reminders' => ['mail' => true, 'database' => true],
            'generated' => ['mail' => false, 'database' => true],
        ],
    ], $user->notifications);
});
