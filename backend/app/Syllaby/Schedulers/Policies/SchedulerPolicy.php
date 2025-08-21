<?php

namespace App\Syllaby\Schedulers\Policies;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Schedulers\Scheduler;
use App\Http\Responses\ErrorCode as Code;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Schedulers\Enums\SchedulerStatus;
use App\Syllaby\Publisher\Publications\Actions\TotalMonthlySchedulesAction;

class SchedulerPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Scheduler $scheduler): bool
    {
        return $user->owns($scheduler);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Scheduler $scheduler): bool
    {
        $allowed = [SchedulerStatus::REVIEWING, SchedulerStatus::DRAFT];

        return $user->owns($scheduler) && in_array($scheduler->status, $allowed);
    }

    /**
     * Determine whether the user can run the model.
     */
    public function run(User $user, Scheduler $scheduler, CreditEventEnum $action, array $input): Response
    {
        $occurrences = $scheduler->rdates();

        if (! $this->hasEnoughPosts($user, $occurrences)) {
            return Response::deny("You've hit the scheduled post limit.", Code::REACH_PLAN_PUBLISH_LIMIT->value);
        }

        if (! $this->scheduledInWeeksRange($occurrences)) {
            return Response::deny("Your plan doesn't allow scheduling this far in advance.", Code::REACH_PLAN_PUBLISH_WEEKS_LIMIT->value);
        }

        if (! $this->hasEnoughCredits($user, $action, $input, $occurrences)) {
            return Response::deny('You do not have enough credits to run this scheduler.', Code::INSUFFICIENT_CREDITS->value);
        }

        if (! $user->owns($scheduler)) {
            return Response::deny('You do not own this scheduler.', Code::GEN_UNAUTHORIZED->value);
        }

        if ($scheduler->status !== SchedulerStatus::REVIEWING) {
            return Response::deny('This scheduler needs to be in reviewing state.', Code::GEN_UNAUTHORIZED->value);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can run the model.
     */
    public function toggle(User $user, Scheduler $scheduler): bool
    {
        $running = [SchedulerStatus::PUBLISHING, SchedulerStatus::PAUSED];

        return $user->owns($scheduler) && in_array($scheduler->status, $running);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Scheduler $scheduler): bool
    {
        return $user->owns($scheduler);
    }

    /**
     * Checks if the user has enough posts to schedule
     */
    private function hasEnoughPosts(User $user, array $occurrences): bool
    {
        $maxScheduledPosts = Feature::value('max_scheduled_posts');

        if ($maxScheduledPosts === '*') {
            return true;
        }

        $scheduledPosts = app(TotalMonthlySchedulesAction::class)->handle($user);

        return (int) $maxScheduledPosts >= ($scheduledPosts + count($occurrences));
    }

    /**
     * Checks if the scheduled date is within the allowed weeks range
     */
    private function scheduledInWeeksRange(array $occurrences): bool
    {
        $weeks = Feature::value('max_scheduled_weeks');

        if ($weeks === '*') {
            return true;
        }

        $start = head($occurrences);
        $end = last($occurrences);

        return $start->diffInWeeks($end) <= (int) $weeks;
    }

    /**
     * Checks if the user has enough credits to run the scheduler
     */
    private function hasEnoughCredits(User $user, CreditEventEnum $action, array $input, array $occurrences): bool
    {
        $duration = Arr::get($input, 'duration', 60);
        $event = config("credit-engine.events.{$action->value}");
        $unit = Arr::get($event, 'min_amount');

        $balance = $user->remaining_credit_amount + $user->extra_credits;

        $cost = ceil($duration / 60) * $unit * count($occurrences);

        return $balance >= $cost;
    }
}
