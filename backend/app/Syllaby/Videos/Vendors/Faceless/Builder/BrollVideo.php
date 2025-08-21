<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Support\Collection;
use App\Syllaby\Assets\Enums\AssetStatus;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BrollVideo extends Source
{
    /**
     * Build the faceless video.
     */
    public function build(): array
    {
        $timeline = collect();

        $voiceover = $this->faceless->getMedia('script');
        $duration = $this->duration($voiceover);

        $this->addBackgroundMusic($timeline);

        $this->addSlider($timeline, $duration);
        $this->addVoiceover($timeline, $voiceover);

        $this->addOverlay($timeline);
        $this->addCaptions($timeline);

        $this->addWatermark($timeline);

        return $this->source($timeline, $duration);
    }

    /**
     * Calculate the duration of the video.
     */
    protected function duration(Collection $audios): float
    {
        return round($audios->sum('custom_properties.duration') / $this->speed, 4);
    }

    /**
     * Add the clips slider to the timeline.
     */
    protected function addSlider(Collection $timeline, float $duration): void
    {
        $assets = $this->faceless->assets()->with('media')
            ->where('status', AssetStatus::SUCCESS)
            ->where('active', true)
            ->oldest('order')
            ->get();

        $clips = $assets->map(fn ($asset) => $asset->getFirstMedia());

        $track = $this->getHighestTrack($timeline) + 1;
        $duration = round($duration / $clips->count(), 4);

        foreach ($clips as $index => $slider) {
            $composition = $this->composition([
                'track' => $track,
                'name' => "broll-slider-{$index}",
            ]);

            $composition['elements'] = $this->addSliderElements($slider, $duration);

            $timeline->push($composition);
        }
    }

    /**
     * Build the clips slider as compositions.
     */
    protected function addSliderElements(Media $slider, float $duration): array
    {
        $elements[] = $this->mediaElement($slider, [
            'track' => 1,
            'id' => $slider->uuid,
            'duration' => $duration,
            'source' => $slider->getFullUrl(),
            'name' => "slider-{$slider->order_column}",
        ]);

        $sfx = Sfx::from($this->faceless->options->sfx);

        if ($sfx !== Sfx::NONE) {
            $elements[] = $this->sfx(['track' => 2, 'source' => $sfx->url()]);
        }

        return $elements;
    }
}
