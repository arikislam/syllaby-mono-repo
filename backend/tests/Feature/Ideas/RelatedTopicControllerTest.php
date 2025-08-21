<?php

namespace Tests\Feature\Ideas;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Topic;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Bookmarks\Bookmark;
use App\Syllaby\Generators\DTOs\ChatResponse;
use App\Http\Middleware\PaidCustomersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Generators\Vendors\Assistants\Chat;
use App\Syllaby\Ideas\Actions\ManageRelatedTopicAction;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('retrieves existing related topics', function () {
    $user = User::factory()->create();

    Topic::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->getJson('/v1/topics')->assertOk();

    expect($response->json('data'))->toHaveKeys([
        'id',
        'title',
        'ideas',
        'type',
        'user_id',
        'metadata',
        'is_bookmarked',
    ]);
})->skip('Pending business constraints confirmation');

it('fetches bookmarked topics', function () {
    $user = User::factory()->create();

    [$t1, $t2, $t3] = Topic::factory()->count(3)->recycle($user)->create();

    Bookmark::factory()->recycle($user)->create([
        'model_id' => $t1->id,
        'model_type' => $t1->getMorphClass(),
    ]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/topics?filter[bookmarked]=true');

    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($t1->id)
        ->title->toBe($t1->title)
        ->ideas->toBe($t1->ideas)
        ->type->toBe($t1->type)
        ->user_id->toBe($t1->user_id)
        ->metadata->toBe($t1->metadata);
})->skip('Pending business constraints confirmation');

it('fetches general topics when user has no topics', function () {
    $user = User::factory()->create();

    Topic::factory()->count(3)->create();
    $generic = Topic::factory()->create(['user_id' => null]);

    $this->actingAs($user);
    $response = $this->getJson('/v1/topics?filter[bookmarked]=false')->assertOk();

    $response->assertOk();

    expect($response->json('data'))
        ->id->toBe($generic->id)
        ->title->toBe($generic->title)
        ->ideas->toBe($generic->ideas);
})->skip('Pending business constraints confirmation');

it('creates a new related topic if it does not exist', function () {
    Chat::shouldReceive('driver')->once()->with('gpt')->andReturnSelf();

    Chat::shouldReceive('send')->once()->andReturn(new ChatResponse(
        text: json_encode(['topics' => ['Idea 1', 'Idea 2', 'Idea 3']]),
        completionTokens: 100
    ));

    $user = User::factory()->create();
    Topic::factory()->for($user)->create();

    $response = $this->actingAs($user)->postJson('/v1/topics/related', [
        'title' => 'New Related Topic',
        'language' => 'en',
    ])->assertOk();

    expect($response->json('data'))
        ->title->toBe('New Related Topic')
        ->user_id->toBe($user->id);

    $this->assertDatabaseCount('related_topics', 2);
})->skip('Pending business constraints confirmation');

it('uses the latest video title when both title and language are null', function () {
    Chat::shouldReceive('driver')->once()->with('gpt')->andReturnSelf();

    Chat::shouldReceive('send')->once()->andReturn(new ChatResponse(
        text: json_encode(['topics' => ['Idea 1', 'Idea 2', 'Idea 3']]),
        completionTokens: 100
    ));

    $user = User::factory()->create();

    Video::factory()->for($user)->completed()->faceless()->create(['title' => 'My Awesome Video']);

    $response = $this->actingAs($user)->postJson('/v1/topics/related', [
        'title' => null,
        'language' => null,
    ])->assertOk();

    expect($response->json('data'))
        ->title->toBe('My Awesome Video')
        ->user_id->toBe($user->id)
        ->ideas->toBe(['Idea 1', 'Idea 2', 'Idea 3']);
})->skip('Pending business constraints confirmation');

it('uses the script content when video title contains untitled', function () {
    Chat::shouldReceive('driver')->once()->with('gpt')->andReturnSelf();

    Chat::shouldReceive('send')->once()->andReturn(new ChatResponse(
        text: json_encode(['topics' => ['Idea 1', 'Idea 2', 'Idea 3']]),
        completionTokens: 100
    ));

    $user = User::factory()->create();

    $video = Video::factory()->for($user)->completed()->faceless()->create(['title' => 'Untitled video project']);

    Faceless::factory()->for($user)->for($video)->create(['script' => $script = 'This is test script']);

    $response = $this->actingAs($user)->postJson('/v1/topics/related', [])->assertOk();

    expect($response->json('data'))
        ->title->toBe($script)
        ->user_id->toBe($user->id)
        ->ideas->toBe(['Idea 1', 'Idea 2', 'Idea 3']);
})->skip('Pending business constraints confirmation');

it('returns general topics without saving to database when video has untitled in name and no script', function () {
    Chat::shouldReceive('driver')->never();

    $user = User::factory()->create();

    $video = Video::factory()->for($user)->completed()->faceless()->create(['title' => 'Untitled video project']);

    Faceless::factory()->for($user)->for($video)->create(['script' => null]);

    $response = $this->actingAs($user)->postJson('/v1/topics/related', [])->assertOk();

    expect($response->json('data'))
        ->title->toBe('General Content Ideas')
        ->user_id->toBe($user->id)
        ->ideas->toBe(ManageRelatedTopicAction::GENERAL_TOPICS);

    $this->assertDatabaseCount('related_topics', 0);
})->skip('Pending business constraints confirmation');

it('returns general topics without saving to database when no title is provided and user has no videos', function () {
    Chat::shouldReceive('driver')->never();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/v1/topics/related', [])->assertOk();

    expect($response->json('data'))
        ->title->toBe('General Content Ideas')
        ->user_id->toBe($user->id)
        ->ideas->toBe(ManageRelatedTopicAction::GENERAL_TOPICS);

    $this->assertDatabaseCount('related_topics', 0);
})->skip('Pending business constraints confirmation');
