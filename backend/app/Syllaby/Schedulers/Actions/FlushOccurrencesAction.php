<?php

namespace App\Syllaby\Schedulers\Actions;

use App\Syllaby\Generators\Generator;
use App\Syllaby\Schedulers\Scheduler;
use App\Syllaby\Schedulers\Occurrence;
use Illuminate\Database\Eloquent\Relations\Relation;

class FlushOccurrencesAction
{
    /**
     * Flush the occurrences.
     */
    public function handle(Scheduler $scheduler): void
    {
        $occurrences = $scheduler->occurrences()->pluck('id');

        Generator::where('model_type', Relation::getMorphAlias(Occurrence::class))
            ->whereIn('model_id', $occurrences)
            ->delete();

        Occurrence::destroy($occurrences);
    }
}
