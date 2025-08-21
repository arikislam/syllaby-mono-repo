<?php

namespace App\Syllaby\Assets\Listeners;

use App\Syllaby\Assets\Asset;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class UpdateAssetOrientation
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        if ($event->media->model_type !== Relation::getMorphAlias(Asset::class)) {
            return;
        }

        $event->media->model()->update(['orientation' => $event->media->getCustomProperty('orientation')]);
    }
}
