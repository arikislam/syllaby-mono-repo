<?php

namespace App\Syllaby\Schedulers\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use App\Syllaby\Schedulers\Enums\SchedulerSource;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Schedulers\Concerns\HandlesRecurrenceRules;

class CreateSchedulerAction
{
    use HandlesRecurrenceRules {
        build as buildRrules;
    }

    /**
     * Handle the creation of a new scheduler.
     */
    public function handle(User $user, array $input): Scheduler
    {
        $scheduler = new Scheduler([
            'user_id' => $user->id,
            'idea_id' => Arr::get($input, 'idea_id'),
            'title' => Arr::get($input, 'topic'),
            'color' => Arr::get($input, 'color'),
            'topic' => Arr::get($input, 'topic'),
            'character_id' =>  Arr::get($input, 'character_id'),
            'source' => SchedulerSource::AI,
            'status' => SchedulerStatus::DRAFT,
            'type' => Arr::get($input, 'type', 'faceless'),
            'rrules' => $this->buildRrules($input),
            'metadata' => $this->metadata($input),
        ]);

        if (Arr::has($input, 'csv')) {
            $scheduler->source = SchedulerSource::CSV;
            $scheduler->status = SchedulerStatus::REVIEWING;
        }

        return DB::transaction(function () use ($scheduler, $input) {
            $scheduler->save();
            $this->bulk($scheduler, Arr::get($input, 'csv', []));
            $scheduler->channels()->sync(Arr::get($input, 'social_channels'));

            return $scheduler;
        });
    }

    /**
     * Get the metadata for the scheduler.
     */
    private function metadata(array $input): array
    {
        return [
            'ai_labels' => Arr::get($input, 'ai_labels', false),
            'custom_description' => Arr::get($input, 'custom_description'),
        ];
    }

    /**
     * Bulk the scheduler.
     */
    private function bulk(Scheduler $scheduler, array $csv): void
    {
        if (blank($csv)) {
            return;
        }

        $rdates = $scheduler->rdates();

        $occurrences = collect($csv)->map(fn ($row, $index) => [
            'occurs_at' => Arr::get($rdates, $index),
            'script' => Arr::get($row, 'script'),
            'topic' => Arr::get($row, 'title'),
            'user_id' => $scheduler->user_id,
            'scheduler_id' => $scheduler->id,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Occurrence::insert($occurrences->toArray());
    }
}
