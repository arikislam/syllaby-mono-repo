<?php

namespace App\Syllaby\Users\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Video;
use App\Syllaby\Speeches\Speech;
use App\Syllaby\RealClones\RealClone;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;

class CalculateStorageAction
{
    /**
     * Calculates the user's storage space usage based on their associated media items.
     */
    public function handle(User $user): int
    {
        return Media::where('user_id', $user->id)
            ->whereIn('model_type', $this->models())
            ->sum('size');
    }

    /**
     * Models to calculate used storage.
     */
    protected function models(): array
    {
        return [
            Relation::getMorphAlias(Asset::class),
            Relation::getMorphAlias(Video::class),
            Relation::getMorphAlias(Speech::class),
            Relation::getMorphAlias(RealClone::class),
            Relation::getMorphAlias(Publication::class),
        ];
    }
}
