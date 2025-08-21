<?php

namespace App\Syllaby\Publisher\Publications\Commands;

use App\Syllaby\Planner\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Syllaby\Publisher\Publications\Jobs\PublishScheduledPublications;

class PublicationsScheduler extends Command
{
    protected $signature = 'publish:scheduled';

    protected $description = 'Publish all scheduled publications';

    public function handle(): int
    {
        $this->events()->chunkById(250, function (Collection $events) {
            Event::whereIn('id', $events->pluck('id'))->touch('completed_at');
            $events->each(fn (Event $event) => dispatch(new PublishScheduledPublications($event)));
        });

        return self::SUCCESS;
    }

    /**
     * Get the scheduled publications
     */
    private function events(): Builder
    {
        return Event::query()->where(fn (Builder $query) => $query->whereNull('completed_at')->whereNull('cancelled_at'))
            ->whereBetween('starts_at', [now()->subMinute()->startOfMinute(), now()])
            ->where('model_type', Relation::getMorphAlias(Publication::class))
            ->select(['id', 'model_id', 'model_type', 'starts_at', 'completed_at', 'cancelled_at']);
    }
}
