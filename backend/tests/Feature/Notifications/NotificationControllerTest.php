<?php

use App\Syllaby\Users\User;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\RealClones\Notifications\RealCloneGenerated;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('can mark all unread notifications as read', function () {
    $user = User::factory()->create();

    collect(range(start: 1, end: 5))->map(fn () => $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => 'real-clone-generated',
        'data' => ['message' => 'Real Clone Generated.'],
    ]));

    $this->assertCount(5, $user->unreadNotifications);

    $this->actingAs($user, 'sanctum')->putJson('v1/notifications')
        ->assertOk()
        ->assertJson([
            'data' => [
                'message' => 'Notifications marked as read.',
            ],
        ]);

    $this->assertCount(0, $user->fresh()->unreadNotifications);
});

it('can mark specific notifications as read', function () {
    $user = User::factory()->create();

    collect(range(start: 1, end: 5))->map(fn () => $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => 'real-clone-generated',
        'data' => ['message' => 'Real Clone Generated.'],
    ]));

    $this->assertCount(5, $notifications = $user->unreadNotifications);

    $this->actingAs($user, 'sanctum')->putJson('v1/notifications', [
        'notifications' => $notifications->take(2)->pluck('id')->toArray(),
    ]);

    $this->assertCount(3, $user->fresh()->unreadNotifications);
});

it('can display all notifications of logged in user', function () {
    $user = User::factory()->create();

    collect(range(start: 1, end: 5))->map(fn () => $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => 'real-clone-generated',
        'data' => ['message' => 'Real Clone Generated.'],
    ]));

    $response = $this->actingAs($user, 'sanctum')
        ->get('v1/notifications')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'notifiable_id',
                    'notifiable_type',
                    'data',
                    'read_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(5)
        ->and($response->json('data.0.type'))->toBe('real-clone-generated')
        ->and($response->json('data.0.notifiable_id'))->toBe($user->id)
        ->and($response->json('data.0.notifiable_type'))->toBe('user');
});

it('can limit notifications', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => RealCloneGenerated::class,
        'data' => ['content' => ['title' => 'Lorem Ipsum']],
    ]);

    $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => RealCloneGenerated::class,
        'data' => ['content' => ['title' => 'Something else']],
    ]);

    $this->assertDatabaseCount('notifications', 2);

    $response = $this->actingAs($user, 'sanctum')
        ->get('v1/notifications?per_page=1')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'notifiable_id',
                    'notifiable_type',
                    'data',
                    'read_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);
});

it('can paginate the notifications', function () {
    $user = User::factory()->create();

    collect(range(start: 1, end: 15))->map(fn () => $user->notifications()->create([
        'id' => Str::uuid(),
        'read_at' => null,
        'type' => 'real-clone-generated',
        'data' => ['message' => 'Real Clone Generated.'],
    ]));

    $this->actingAs($user, 'sanctum')
        ->get('v1/notifications')
        ->assertJsonCount(12, 'data');

    $this->actingAs($user, 'sanctum')
        ->get('v1/notifications?page=2')
        ->assertJsonCount(3, 'data');
});
