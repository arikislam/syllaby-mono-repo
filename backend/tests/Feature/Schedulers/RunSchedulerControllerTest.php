<?php

namespace Tests\Feature\Schedulers;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use App\Syllaby\Assets\Media;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Speeches\Voice;
use Illuminate\Bus\PendingBatch;
use App\Http\Responses\ErrorCode;
use App\Syllaby\Characters\Genre;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Videos\Enums\TextPosition;
use App\Syllaby\Videos\Enums\CaptionEffect;
use Database\Seeders\CreditEventTableSeeder;
use App\Http\Middleware\PaidCustomersMiddleware;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CreditEventTableSeeder::class);
    $this->withoutMiddleware(PaidCustomersMiddleware::class);
});

it('runs a scheduler successfully', function () {
    Bus::fake();

    Feature::define('max_scheduled_weeks', 4);
    Feature::define('max_scheduled_posts', 20);

    Carbon::setTestNow('2024-10-01');

    $user = User::factory()->create();
    $folder = Folder::factory()->recycle($user)->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
        'metadata' => [
            'ai_labels' => true,
            'custom_description' => 'My first custom description',
        ],
    ]);

    Occurrence::factory()->for($scheduler)->count(3)->create();

    $voice = Voice::factory()->create();
    $music = Media::factory()->create();

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'duration' => 60,
            'voice_id' => $voice->id,
            'language' => 'english',
            'type' => FacelessType::AI_VISUALS->value,
            'aspect_ratio' => '16:9',
            'genre_id' => $genre->id,
            'transition' => Transition::SCALE->value,
            'music_id' => $music->id,
            'music_volume' => 'medium',
            'sfx' => Sfx::NONE->value,
        ],
        'captions' => [
            'font_color' => 'default',
            'font_family' => 'lively',
            'position' => TextPosition::CENTER->value,
            'effect' => CaptionEffect::BOUNCE->value,
        ],
        'destination_id' => $folder->resource->id,
    ]);

    $scheduler->refresh();

    Bus::chainedBatch(fn (PendingBatch $batch) => $batch->jobs->count() === 4);

    expect($response->json('data'))
        ->status->toBe(SchedulerStatus::GENERATING->value)
        ->and($response->json('data.options'))
        ->toHaveKey('duration', 60)
        ->toHaveKey('voice_id', $voice->id)
        ->toHaveKey('language', 'english')
        ->toHaveKey('aspect_ratio', '16:9')
        ->toHaveKey('genre', $genre->id)
        ->toHaveKey('transition', Transition::SCALE->value)
        ->toHaveKey('music_id', $music->id)
        ->toHaveKey('music_volume', 'medium')
        ->toHaveKey('sfx', Sfx::NONE->value)
        ->and($response->json('data.options.captions'))
        ->toHaveKey('font_color', 'default')
        ->toHaveKey('font_family', 'lively')
        ->toHaveKey('position', TextPosition::CENTER->value)
        ->toHaveKey('effect', CaptionEffect::BOUNCE->value);

    $this->assertDatabaseHas('schedulers', [
        'title' => $scheduler->title,
        'metadata->ai_labels' => true,
        'metadata->custom_description' => 'My first custom description',
        'metadata->destination' => $folder->resource->id,
    ]);
});

it('fails to run a scheduler when user does not own the scheduler', function () {
    Bus::fake();

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->create();

    $this->actingAs($user);
    $this->postJson("/v1/schedulers/{$scheduler->id}/run")->assertForbidden();
});

it('fails to run a scheduler when its status is not reviewing', function () {
    Bus::fake();

    $user = User::factory()->create();
    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::WRITING,
    ]);

    $this->actingAs($user);
    $this->postJson("/v1/schedulers/{$scheduler->id}/run")->assertForbidden();
});

it('fails to run a scheduler with insufficient credits', function () {
    Bus::fake();

    Carbon::setTestNow('2024-10-01');

    Feature::define('max_scheduled_weeks', 4);
    Feature::define('max_scheduled_posts', 20);

    $user = User::factory()->create([
        'remaining_credit_amount' => 20,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'duration' => 60,
        ],
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => ErrorCode::INSUFFICIENT_CREDITS->value,
    ]);
});

it('fails to run a scheduler when scheduled posts limit is reached', function () {
    Bus::fake();

    Carbon::setTestNow('2024-10-01');

    Feature::define('max_scheduled_weeks', 4);
    Feature::define('max_scheduled_posts', 2);

    $user = User::factory()->create([
        'remaining_credit_amount' => 20,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'duration' => 60,
        ],
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => ErrorCode::REACH_PLAN_PUBLISH_LIMIT->value,
    ]);
});

it('fails to run a scheduler when scheduled dates are out of range for the plan', function () {
    Bus::fake();

    Carbon::setTestNow('2024-10-01');

    Feature::define('max_scheduled_weeks', 1);
    Feature::define('max_scheduled_posts', 20);

    $user = User::factory()->create([
        'remaining_credit_amount' => 20,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'type' => FacelessType::AI_VISUALS->value,
            'duration' => 60,
        ],
    ]);

    $response->assertForbidden()->assertJsonFragment([
        'code' => ErrorCode::REACH_PLAN_PUBLISH_WEEKS_LIMIT->value,
    ]);
});

it('fails to run a scheduler when occurrences have empty scripts', function () {
    Bus::fake();

    Feature::define('max_scheduled_weeks', 4);
    Feature::define('max_scheduled_posts', 20);

    Carbon::setTestNow('2024-10-01');

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241002T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241004T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
            "DTSTART:20241020T000000Z\nRRULE:FREQ=DAILY;COUNT=1",
        ],
    ]);

    Occurrence::factory()->recycle($scheduler)->create([
        'script' => null,
    ]);

    $voice = Voice::factory()->create();
    $folder = Folder::factory()->recycle($user)->create();

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'duration' => 60,
            'voice_id' => $voice->id,
            'language' => 'english',
            'type' => FacelessType::AI_VISUALS->value,
            'aspect_ratio' => '16:9',
            'genre_id' => $genre->id,
            'transition' => Transition::SCALE->value,
            'music_volume' => 'medium',
            'sfx' => Sfx::NONE->value,
        ],
        'captions' => [
            'font_color' => 'default',
            'font_family' => 'lively',
            'position' => TextPosition::CENTER->value,
            'effect' => CaptionEffect::BOUNCE->value,
        ],
        'destination_id' => $folder->resource->id,
    ]);

    $response->assertUnprocessable()->assertJsonFragment([
        'errors' => [
            'scripts' => ['All occurrences must have scripts before running the scheduler.'],
        ],
    ]);
});

it('updates scheduler start date and its occurrences when running a scheduler created in the past', function () {
    Bus::fake();
    Feature::define('max_scheduled_weeks', 4);
    Feature::define('max_scheduled_posts', 20);

    Carbon::setTestNow('2024-10-10');

    $user = User::factory()->create();

    $genre = Genre::factory()->active()->consistent()->create([
        'name' => 'Action Movie',
        'slug' => StoryGenre::ACTION_MOVIE->value,
    ]);

    $voice = Voice::factory()->create();
    $music = Media::factory()->create();

    $scheduler = Scheduler::factory()->for($user)->create([
        'status' => SchedulerStatus::REVIEWING,
        'rrules' => [
            "DTSTART:20241006T120000Z\nRRULE:FREQ=DAILY;COUNT=3",
        ],
    ]);

    $occurrences = Occurrence::factory()->recycle($scheduler)->count(3)->sequence(
        ['occurs_at' => '2024-10-06 12:00:00', 'status' => 'completed'],
        ['occurs_at' => '2024-10-07 12:00:00', 'status' => 'completed'],
        ['occurs_at' => '2024-10-08 12:00:00', 'status' => 'completed'],
    )->create();

    expect($occurrences)->sequence(
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-06 12:00:00'),
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-07 12:00:00'),
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-08 12:00:00'),
    );

    $this->actingAs($user);
    $response = $this->postJson("/v1/schedulers/{$scheduler->id}/run", [
        'options' => [
            'duration' => 60,
            'voice_id' => $voice->id,
            'language' => 'english',
            'type' => FacelessType::AI_VISUALS->value,
            'aspect_ratio' => '16:9',
            'genre_id' => $genre->id,
            'transition' => Transition::SCALE->value,
            'music_id' => $music->id,
            'music_volume' => 'medium',
            'sfx' => Sfx::NONE->value,
        ],
        'captions' => [
            'font_color' => 'default',
            'font_family' => 'lively',
            'position' => TextPosition::CENTER->value,
            'effect' => CaptionEffect::BOUNCE->value,
        ],
    ]);

    expect($scheduler->refresh()->occurrences)->sequence(
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-11 12:00:00'),
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-12 12:00:00'),
        fn ($occurrence) => $occurrence->occurs_at->toDateTimeString()->toBe('2024-10-13 12:00:00'),
    );
});

it('fails to run a scheduler when a social network feature is disabled', function () {})->todo();
