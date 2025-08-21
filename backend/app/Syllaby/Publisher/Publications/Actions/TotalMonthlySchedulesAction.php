<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use App\Syllaby\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use App\Syllaby\Publisher\Publications\AccountPublication;

class TotalMonthlySchedulesAction
{
    /**
     * Gets the total user's scheduled publications created
     * in the current month
     */
    public function handle(User $user): int
    {
        $today = now();
        $start = $today->startOfMonth()->toDateTimeString();
        $end = $today->endOfMonth()->toDateTimeString();

        return AccountPublication::query()
            ->join('publications', $this->permanent())
            ->join('events', $this->scheduled())
            ->where('events.user_id', $user->id)
            ->where('publications.user_id', $user->id)
            ->where('publications.draft', false)
            ->where('publications.temporary', false)
            ->whereNull('events.completed_at')
            ->whereColumn('events.created_at', '<>', 'events.starts_at')
            ->whereBetween('publications.created_at', [$start, $end])
            ->count();
    }

    /**
     * Join Clause for publications table
     */
    private function permanent(): callable
    {
        return fn (JoinClause $join) => $join->on('publications.id', '=', 'account_publications.publication_id');
    }

    /**
     * Join clause for  events table
     */
    private function scheduled(): callable
    {
        return fn (JoinClause $join) => $join->on(function ($join) {
            $join->on('events.model_id', '=', 'publications.id')->on('events.model_type', DB::raw("'publication'"));
        });
    }
}
