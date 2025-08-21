<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;
use App\Syllaby\Videos\Enums\Sfx;
use Illuminate\Support\Collection;
use App\Syllaby\Videos\Enums\Transition;
use App\Syllaby\Assets\Enums\AssetStatus;

class AiVideo extends Source
{
    /**
     * Build the AI video.
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
     * Add the images slider to the timeline.
     */
    protected function addSlider(Collection $timeline, float $duration): void
    {
        $assets = $this->faceless->assets()->with('media')
            ->where('status', AssetStatus::SUCCESS)
            ->where('active', true)
            ->oldest('order')
            ->get();

        $images = $assets->map(fn ($asset) => $asset->getFirstMedia());

        $delay = 0.0;
        $duration = round($duration / $images->count(), 4);

        $track = $this->getHighestTrack($timeline) + 1;
        $transition = $this->faceless->options->transition;

        foreach ($images as $index => $slider) {
            $composition = $this->composition([
                'track' => $track,
                'name' => "ai-slider-{$index}",
            ]);

            if (filled($transition) && $index > 0) {
                $config = $transition == 'mixed' ? Transition::MIXED->getConfig() : Transition::from($transition)->getConfig();
                $composition['animations'] = [$config];
                $delay = Arr::get($config, 'duration', 0.0);
            }

            $timeOnScreen = round($duration + $delay, 4);
            $composition['elements'] = $this->addSliderElements($slider, $timeOnScreen);

            $timeline->push($composition);
        }
    }

    /**
     * Build the images slider as compositions.
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
