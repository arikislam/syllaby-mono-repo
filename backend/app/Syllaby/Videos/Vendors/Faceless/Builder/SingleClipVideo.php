<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use App\Syllaby\Assets\Asset;
use Illuminate\Support\Collection;

class SingleClipVideo extends Source
{
    /**
     * Build the single clip video.
     */
    public function build(): array
    {
        $timeline = collect();

        $voiceover = $this->faceless->getMedia('script');
        $duration = $this->duration($voiceover);

        $this->addBackgroundMusic($timeline);

        $this->addBackgroundClip($timeline);
        $this->addVoiceover($timeline, $voiceover);

        $this->addOverlay($timeline);
        $this->addCaptions($timeline);

        $this->addWatermark($timeline);

        return $this->source($timeline, $duration);
    }

    /**
     * Get the duration of the video.
     */
    protected function duration(Collection $audios): float
    {
        return round($audios->sum('custom_properties.duration') / $this->speed, 4);
    }

    /**
     * Add the background clip to the timeline.
     */
    protected function addBackgroundClip(Collection $timeline): void
    {
        $asset = Asset::where('id', $this->faceless->background_id)->first()->getFirstMedia();

        $timeline->push($this->video([
            'loop' => true,
            'duration' => null,
            'id' => $asset->uuid,
            'source' => $asset->getFullUrl(),
            'track' => $this->getHighestTrack($timeline) + 1,
        ]));
    }
}
