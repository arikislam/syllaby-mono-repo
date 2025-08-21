<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Support\Collection;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Assets\Enums\AssetStatus;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UrlBasedVideo extends Source
{
    /**
     * Build the url-based video.
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

        $this->source = $this->optimize($duration);

        return array_merge($this->source, ['elements' => $timeline->toArray()]);
    }

    /**
     * Calculate the duration of the video.
     */
    protected function duration(Collection $audios): float
    {
        return round($audios->sum('custom_properties.duration') / $this->speed, 4);
    }

    /**
     * Add the scraped images slider to the timeline.
     */
    protected function addSlider(Collection $timeline, float $duration): void
    {
        $assets = $this->faceless->assets()
            ->with('media')
            ->where('active', true)
            ->where('status', AssetStatus::SUCCESS)
            ->oldest('order')
            ->get();

        $track = $this->getHighestTrack($timeline) + 1;

        $delay = 0.0;
        $duration = round($duration / $assets->count(), 4);

        $transition = $this->faceless->options->transition;

        foreach ($assets as $index => $asset) {
            $media = $asset->getFirstMedia();

            $composition = $this->composition([
                'track' => $track,
                'name' => "url-slider-{$index}",
            ]);

            if (filled($transition) && $index > 0) {
                $config = $transition == 'mixed' ? Transition::MIXED->getConfig() : Transition::from($transition)->getConfig();
                $composition['animations'] = [$config];
                $delay = Arr::get($config, 'duration', 0.0);
            }

            $timeOnScreen = round($duration + $delay, 4);
            $composition['elements'] = $this->addSliderElements($media, $timeOnScreen);

            $timeline->push($composition);
        }
    }

    /**
     * Build the slider elements as compositions.
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
