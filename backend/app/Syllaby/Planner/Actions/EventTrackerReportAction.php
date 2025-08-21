<?php

namespace App\Syllaby\Planner\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Planner\Event;
use Illuminate\Support\Facades\DB;

class EventTrackerReportAction
{
    public function handle(User $user, string $startDate, string $endDate): array
    {
        $events = Event::where('user_id', $user->id)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN completed_at IS NOT null THEN 1 END) as completed'),
            ])
            ->first();

        return [
            'total' => (int) $events->total,
            'completed' => (int) $events->completed,
            'percentage' => $this->percentage($events->total ?? 0, $events->completed ?? 0),
        ];
    }

    private function percentage(int $total, int $completed): int
    {
        if ($total === 0 || $completed === 0) {
            return 0;
        }

        return round(($completed / $total) * 100);
    }
}
