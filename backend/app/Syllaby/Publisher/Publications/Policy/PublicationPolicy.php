<?php

namespace App\Syllaby\Publisher\Publications\Policy;

use Carbon\Carbon;
use App\Syllaby\Users\User;
use Laravel\Pennant\Feature;
use Illuminate\Auth\Access\Response;
use App\Http\Responses\ErrorCode as Code;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Publications\Actions\TotalMonthlySchedulesAction;

class PublicationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Publication $publication): bool
    {
        return $user->owns($publication);
    }

    public function create(User $user, Publication $publication, string $platform, ?string $scheduled): Response
    {
        if (Feature::inactive("publish_{$platform}")) {
            return Response::deny("Current plan doesn't support this feature.", Code::FEATURE_NOT_ALLOWED->value);
        }

        if (blank($scheduled)) {
            return Response::allow();
        }

        if (! $this->hasEnoughPosts($user, $publication)) {
            return Response::deny("You've hit the scheduled post limit.", Code::REACH_PLAN_PUBLISH_LIMIT->value);
        }

        if (Carbon::parse($scheduled)->isPast()) {
            return Response::deny("Oops! You can't schedule posts in the past. Try picking a future time.", Code::GEN_WRONG_ARGS->value);
        }

        if (! $this->scheduledInWeeksRange($scheduled)) {
            return Response::deny("Your plan doesn't allow scheduling this far in advance.", Code::REACH_PLAN_PUBLISH_WEEKS_LIMIT->value);
        }

        return Response::allow();
    }

    public function update(User $user, Publication $publication): bool
    {
        return $user->owns($publication);
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $user->owns($publication);
    }

    /**
     * [hasEnoughPosts description]
     */
    private function hasEnoughPosts(User $user, Publication $publication): bool
    {
        if ($publication->scheduled) {
            return true;
        }

        $maxScheduledPosts = Feature::value('max_scheduled_posts');

        if ($maxScheduledPosts === '*') {
            return true;
        }

        $scheduledPosts = app(TotalMonthlySchedulesAction::class)->handle($user);

        return (int) $maxScheduledPosts >= $scheduledPosts;
    }

    /**
     * [scheduledInWeeksRange description]
     */
    private function scheduledInWeeksRange(?string $scheduled): bool
    {
        $weeks = Feature::value('max_scheduled_weeks');

        if ($weeks === '*') {
            return true;
        }

        $start = Carbon::now();
        $end = $start->copy()->addWeeks((int) $weeks);

        return Carbon::parse($scheduled)->between($start, $end);
    }
}
