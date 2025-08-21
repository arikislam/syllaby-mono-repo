<?php

namespace App\Syllaby\Videos\Jobs;

use DB;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Video;
use App\Syllaby\Planner\Event;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use App\Syllaby\Publisher\Publications\Publication;
use Illuminate\Database\Eloquent\Relations\Relation;

class DeleteVideo implements ShouldQueue
{
    use Queueable;

    public function __construct(public Video $video, public bool $deleteUnusedAssets) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $this->removeAssets();
            $this->removeFootage();
            $this->removeFaceless();
            $this->removeLeftover();
        });
    }

    private function removeAssets(): void
    {
        if ($this->video->type !== Video::FACELESS) {
            return;
        }

        $this->video->loadMissing('faceless');

        if (! $this->video->faceless) {
            return;
        }

        if (! $this->deleteUnusedAssets) {
            $this->video->faceless->assets()->detach();
        } else {
            $this->video->faceless
                ->assets()
                ->withCount('videos')
                ->chunkById(50, function (Collection $assets) {
                    $this->video->faceless
                        ->assets()
                        ->detach($assets->pluck('id')->all());

                    return $assets->filter(function ($asset) {
                        return $asset->videos_count === 1;
                    })->each(fn (Asset $asset) => $asset->delete());
                });
        }
    }

    private function removeFootage(): void
    {
        if ($this->video->type !== Video::CUSTOM) {
            return;
        }

        $this->video->loadMissing('footage');

        if (! $this->video->footage) {
            return;
        }

        $this->video->footage->loadMissing('clones');

        if ($clones = $this->video->footage->clones) {
            $clones->load(['speech'])->each(function ($clone) {
                $clone->generator()->delete();
                $clone->speech?->delete();
                $clone->delete();
            });
        }

        $this->video->footage->timeline()->delete();
        $this->video->footage->delete();
    }

    private function removeFaceless(): void
    {
        if ($this->video->type !== Video::FACELESS) {
            return;
        }

        $this->video->loadMissing('faceless');

        if (! $this->video->faceless) {
            return;
        }

        $this->video->faceless->trackers()->delete();
        $this->video->faceless->generator()->delete();

        $this->video->faceless->captions()->delete();
        $this->video->faceless->timeline()->delete();

        $this->video->faceless->delete();
    }

    private function removeLeftover(): void
    {
        $this->cancelPublications();
        $this->video->resource()->delete();
        $this->video->delete();
    }

    private function cancelPublications(): void
    {
        $publications = $this->video->publications()->pluck('id');

        if ($publications->isEmpty()) {
            return;
        }

        Event::where('model_type', Relation::getMorphAlias(Publication::class))
            ->whereIn('model_id', $publications)
            ->update(['cancelled_at' => now()]);
    }
}
