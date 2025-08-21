<?php

namespace App\Syllaby\Videos\Vendors\Faceless\Builder;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Asset;
use App\Syllaby\Videos\Faceless;
use Illuminate\Support\Collection;
use App\Syllaby\Videos\DTOs\Options;
use App\Syllaby\Videos\Enums\Overlay;
use App\System\Traits\DetectsLanguage;
use App\Syllaby\Videos\Enums\Dimension;
use App\System\Traits\HandlesWatermark;
use App\Syllaby\Videos\Enums\TextPosition;
use App\Syllaby\Videos\Enums\WatermarkPosition;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

abstract class Source
{
    use DetectsLanguage, HandlesWatermark;

    /**
     * The captions language.
     */
    protected string $language;

    /**
     * The speed of the video.
     */
    protected float $speed = 1;

    /**
     * The source of the video.
     */
    protected array $source = [];

    /**
     * The dimension of the video.
     */
    protected Dimension $dimension;

    /**
     * Create a new faceless source builder instance.
     */
    public function __construct(protected Faceless $faceless, protected array $captions)
    {
        $this->language = $this->language();
        $this->dimension = Dimension::fromAspectRatio($faceless->options->aspect_ratio);
        $this->source = $this->defaults();
    }

    /**
     * Build the source of the video.
     */
    abstract public function build(): array;

    /**
     * Calculate the duration of the video.
     */
    abstract protected function duration(Collection $audios): float;

    /**
     * The default source of faceless video.
     */
    public function defaults(): array
    {
        return [
            'output_format' => 'mp4',
            'frame_rate' => '25 fps',
            'width' => $this->dimension->get('width'),
            'height' => $this->dimension->get('height'),
            'elements' => [],
        ];
    }

    /**
     * Build the source of the video.
     */
    protected function source(Collection $timeline, float $duration): array
    {
        $user = $this->faceless->user;

        if ($user->onTrial() || ! $user->subscribed()) {
            $timeline->push($this->syllabyWatermarkElement());
        }

        $this->source = $this->optimize($duration);

        return array_merge($this->source, ['elements' => $timeline->toArray()]);
    }

    /**
     * Generic composition element attributes.
     */
    protected function composition(array $overrides = []): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'type' => 'composition',
            'name' => 'genre-slider',
            'track' => 1,
            'elements' => [],
        ], $overrides);
    }

    /**
     * Generic video element attributes.
     */
    protected function video(array $overrides = []): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'background',
            'type' => 'video',
            'track' => 1,
            'time' => 0,
            'loop' => true,
            'volume' => '0%',
            'source' => null,
        ], $overrides);
    }

    /**
     * Generic image element attributes.
     */
    protected function image(array $overrides = []): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'slider',
            'type' => 'image',
            'track' => 1,
            'time' => 0,
            'source' => null,
            'animations' => [[
                'easing' => 'cubic-bezier(0, 0.7, 0.5, 1)',
                'type' => 'pan',
                'end_x' => '50%',
                'scope' => 'element',
                'start_x' => '50%',
                'end_scale' => '100%',
                'start_scale' => '190%',
            ]],
        ], $overrides);
    }

    /**
     * Generic audio element attributes.
     */
    protected function audio(array $overrides = []): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'voiceover',
            'type' => 'audio',
            'track' => 2,
            'source' => null,
            'time' => 0,
        ], $overrides);
    }

    /**
     * Sound effect element attributes.
     */
    protected function sfx(array $overrides = []): array
    {
        $element = array_merge([
            'id' => Str::uuid()->toString(),
            'name' => 'sfx',
            'track' => 2,
            'time' => 0,
            'volume' => '14%',
            'trim_start' => 0.2,
        ], $overrides);

        return $this->audio($element);
    }

    public function addWatermark(Collection $timeline): void
    {
        if ($this->faceless->watermark_id === null || $this->faceless->options->watermark_position === WatermarkPosition::NONE->value) {
            return;
        }

        $watermark = Asset::where('id', $this->faceless->watermark_id)->first();

        $position = $this->faceless->options->watermark_position;

        $opacity = $this->faceless->options->watermark_opacity;

        $timeline->push($this->getWatermarkSource($watermark, $timeline, $position, $opacity));
    }

    /**
     * Add the background music to the timeline.
     */
    protected function addBackgroundMusic(Collection $timeline): void
    {
        if (! $this->faceless->music_id) {
            return;
        }

        $music = $this->faceless->music;

        $volumes = ['low' => '10%', 'medium' => '20%', 'high' => '38%'];

        $composition = $this->composition([
            'id' => Str::uuid()->toString(),
            'track' => 1,
            'name' => 'background-music',
            'elements' => [
                $this->audio([
                    'id' => $music->uuid,
                    'time' => 0,
                    'loop' => true,
                    'duration' => null,
                    'audio_fade_out' => 2,
                    'source' => $music->getFullUrl(),
                    'volume' => Arr::get($volumes, $this->faceless->options->volume, 'medium'),
                ]),
            ],
        ]);

        $timeline->push($composition);
    }

    /**
     * Add the voiceover to the timeline.
     */
    protected function addVoiceover(Collection $timeline, Collection $voiceover): void
    {
        $track = $this->getHighestTrack($timeline) + 1;

        foreach ($voiceover as $item) {
            $timeline->push($this->audio([
                'time' => 0,
                'track' => $track,
                'id' => $item->uuid,
                'source' => $item->getFullUrl(),
                'speed' => ($this->speed * 100).'%',
                'name' => "voiceover-{$item->order_column}",
                'duration' => $item->getCustomProperty('duration'),
            ]));
        }
    }

    /**
     * Add the overlay to the timeline.
     */
    protected function addOverlay(Collection $timeline): void
    {
        $overlay = Overlay::from($this->faceless->options->overlay);

        if ($overlay === Overlay::NONE) {
            return;
        }

        $track = $this->getHighestTrack($timeline) + 1;

        $timeline->push($overlay->getConfig(fn () => [
            'track' => $track,
        ]));
    }

    /**
     * Add the captions to the timeline.
     */
    protected function addCaptions(Collection $timeline): void
    {
        if ($this->faceless->options->font_family === 'none') {
            return;
        }

        $track = $this->getHighestTrack($timeline) + 1;

        $words = Arr::flatten(Arr::pluck($this->captions, 'words'), 1);

        $timeline->push(
            $this->captions($this->faceless->options, $words, $track)
        );
    }

    /**
     * Captions styles preferences and normalization.
     */
    protected function captions(Options $options, array $words, int $track): array
    {
        $transcript = collect($words)->map(fn ($word) => [
            'value' => $word['text'],
            'time' => round($word['start'] / $this->speed, 4),
            'duration' => round(($word['end'] / $this->speed) - ($word['start'] / $this->speed), 4),
        ])->values()->toArray();

        $overrides = fn ($element) => [
            'time' => 0,
            'track' => $track,
            'name' => 'captions',
            'transcript_source' => $transcript,
            'x_alignment' => TextPosition::from($options->position)->coordinates('x', $this->dimension->value),
            'y_alignment' => TextPosition::from($options->position)->coordinates('y', $this->dimension->value),
            'transcript_color' => $options->font_color === 'default' ? $element['transcript_color'] : $options->font_color,
            'transcript_effect' => $options->caption_effect === 'none' ? $element['transcript_effect'] : $options->caption_effect,
        ];

        if (! $this->hasLanguageSupport($this->language)) {
            return FontPresets::get('noto', $overrides);
        }

        return FontPresets::get($options->font_family, $overrides);
    }

    /**
     * Decides the type of the media element.
     */
    protected function mediaElement(Media $media, array $overrides = []): array
    {
        if (Str::startsWith($media->mime_type, 'image/')) {
            return $this->image($overrides);
        }

        return $this->video($overrides);
    }

    /**
     * Get the highest track number in the source.
     */
    protected function getHighestTrack(Collection $source): int
    {
        return $source->max('track') ?? 0;
    }

    /**
     * Optimize the source for multipart videos.
     */
    protected function optimize(float $duration): array
    {
        $multipart = [];

        if ($duration >= 240) {
            $multipart = ['multipart' => 120, 'prefetch' => true];
        }

        return array_merge($this->source, $multipart);
    }

    /**
     * Get the script language of the video.
     */
    private function language(): string
    {
        if ($generator = $this->faceless->generator) {
            return $this->locale($generator->language);
        }

        return $this->detectLanguage($this->faceless->script);
    }

    private function getWatermarkSource(Asset $watermark, Collection $timeline, ?string $position = WatermarkPosition::BOTTOM_RIGHT->value, ?string $opacity = '0.5'): array
    {
        if (blank($watermark->getFirstMediaUrl())) {
            throw new RuntimeException("The watermark asset doesn't have a valid url.");
        }

        return [
            'id' => Str::uuid()->toString(),
            'name' => sprintf('watermark-%s', $watermark->id),
            'type' => 'image',
            'track' => $this->getHighestTrack($timeline) + 1,
            'x' => WatermarkPosition::from($position)->coordinates('x'),
            'y' => WatermarkPosition::from($position)->coordinates('y'),
            'width' => $watermark->getFirstMedia()->getCustomProperty('width', 150),
            'height' => $watermark->getFirstMedia()->getCustomProperty('height', 150),
            'aspect_ratio' => 1,
            'opacity' => "{$opacity}%",
            'x_anchor' => WatermarkPosition::from($position)->coordinates('x'),
            'y_anchor' => WatermarkPosition::from($position)->coordinates('y'),
            'source' => $watermark->getFirstMediaUrl(),
        ];
    }
}
