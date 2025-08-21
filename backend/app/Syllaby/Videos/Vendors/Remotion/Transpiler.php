<?php

namespace App\Syllaby\Videos\Vendors\Remotion;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Syllaby\Videos\Enums\FacelessType;

class Transpiler
{
    protected int $width = 720;

    protected int $height = 1280;

    protected float $duration = 0;

    public function handle(array $source, string $type): array
    {
        $elements = collect($source['elements']);

        $this->duration = $this->extractDuration($elements);
        $this->width = Arr::get($source, 'width', $this->width);
        $this->height = Arr::get($source, 'height', $this->height);

        $fps = (int) Str::before(Arr::get($source, 'frame_rate', '25 fps'), ' fps');

        return [
            'sourceElements' => [
                'frameRate' => $fps,
                'duration' => $this->duration,
                'width' => $this->width,
                'height' => $this->height,
                'elements' => $this->extractElements($elements, $type),
                'subtitles' => $this->extractSubtitles($elements),
                'soundEffect' => $this->extractSoundEffect($elements),
                'voiceOver' => $this->extractVoiceOver($elements),
                'watermark' => $this->extractWatermark($elements),
                'overlayEffect' => $this->extractOverlayEffect($elements),
                'backgroundMusic' => $this->extractBackgroundMusic($elements),
                'syllabyWatermark' => $this->extractSyllabyWatermark($elements),
            ],
        ];
    }

    private function extractDuration(Collection $elements): float
    {
        $duration = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'audio' && Str::startsWith(Arr::get($element, 'name'), 'voiceover-');
        })->pluck('duration')->sum();

        return round($duration, 6);
    }

    private function extractElements(Collection $elements, string $type): array
    {
        if ($type === FacelessType::SINGLE_CLIP->value) {
            return $this->extractSingleClip($elements);
        }

        return $this->extractSliders($elements);
    }

    private function extractSingleClip(Collection $elements): array
    {
        $element = $elements->filter(function (array $element) {
            return Arr::get($element, 'name') === 'background';
        })->values()->first();

        return [[
            'scaleEffect' => false,
            'duration' => $this->duration,
            'animationEffect' => null,
            'id' => Arr::get($element, 'id'),
            'type' => Arr::get($element, 'type'),
            'name' => Arr::get($element, 'name'),
            'source' => Arr::get($element, 'source'),
            'loop' => Arr::get($element, 'loop', false),
            'time' => round(Arr::get($element, 'time'), 6),
            'width' => Arr::get($element, 'width', $this->width),
            'height' => Arr::get($element, 'height', $this->height),
            'volume' => $this->getVolumeFromPercentage(Arr::get($element, 'volume', '100%')),
        ]];
    }

    private function extractSliders(Collection $elements): array
    {
        $compositions = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'composition' && Str::startsWith(Arr::get($element, 'elements.0.name'), 'slider-');
        })->values();

        $animation = Arr::get($compositions->get(1), 'animations.0', []);

        $slides = $compositions->map(function (array $composition, $index) use ($animation, $compositions) {
            $last = $index === $compositions->count() - 1;
            $element = Arr::get($composition, 'elements.0');
            $animation = Arr::get($composition, 'animations.0', $animation);

            return [
                'scaleEffect' => true,
                'id' => Arr::get($element, 'id'),
                'type' => Arr::get($element, 'type'),
                'name' => Arr::get($element, 'name'),
                'source' => Arr::get($element, 'source'),
                'loop' => Arr::get($element, 'loop', false),
                'time' => round(Arr::get($element, 'time'), 6),
                'width' => Arr::get($element, 'width', $this->width),
                'duration' => round(Arr::get($element, 'duration'), 6),
                'height' => Arr::get($element, 'height', $this->height),
                'animationEffect' => $this->extractAnimationEffect($animation, $last),
                'volume' => $this->getVolumeFromPercentage(Arr::get($element, 'volume', '100%')),
            ];
        });

        $delay = Arr::get($animation, 'duration', 0.5);

        return $slides->values()->reduce(function (array $carry, array $slider) use ($delay) {
            $lastTime = empty($carry) ? 0 : (end($carry)['time'] + end($carry)['duration']) - $delay;

            return [...$carry, array_merge($slider, ['time' => round($lastTime, 6)])];
        }, []);
    }

    private function extractVoiceOver(Collection $elements): ?array
    {
        $audio = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'audio' && Str::startsWith(Arr::get($element, 'name'), 'voiceover-');
        })->first();

        if (empty($audio)) {
            return null;
        }

        $time = Arr::get($audio, 'time', 0);

        return [
            'source' => Arr::get($audio, 'source'),
            'time' => $time ?? 0,
            'duration' => round($this->duration, 6),
            'id' => Arr::get($audio, 'id'),
            'name' => Arr::get($audio, 'name'),
            'type' => Arr::get($audio, 'type', 'audio'),
            'volume' => $this->getVolumeFromPercentage(Arr::get($audio, 'volume', '100%')),
            'playbackRate' => $this->getPlaybackRateFromPercentage(Arr::get($audio, 'speed', '100%')),
        ];
    }

    private function extractBackgroundMusic(Collection $elements): ?array
    {
        $music = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'composition' && Arr::get($element, 'name') === 'background-music';
        })->first();

        if (empty($music)) {
            return null;
        }

        $music = Arr::get($music, 'elements.0');

        return [
            'source' => Arr::get($music, 'source'),
            'time' => Arr::get($music, 'time', 0),
            'duration' => Arr::get($music, 'duration') ?? $this->duration,
            'id' => Arr::get($music, 'id'),
            'name' => Arr::get($music, 'name'),
            'type' => Arr::get($music, 'type', 'audio'),
            'volume' => $this->getVolumeFromPercentage(Arr::get($music, 'volume', '100%')),
            'playbackRate' => $this->getPlaybackRateFromPercentage(Arr::get($music, 'playback_rate', '100%')),
        ];
    }

    private function extractSubtitles(Collection $elements): ?array
    {
        $subtitles = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'text' && Arr::get($element, 'name') === 'captions';
        })->first();

        if (empty($subtitles)) {
            return null;
        }

        $element = [
            'captions' => [],
            'fontFamily' => Arr::get($subtitles, 'font_family'),
            // 'size' => Arr::get($subtitles, 'size', null),
            'fillColor' => Arr::get($subtitles, 'fill_color', null),
            'color' => Arr::get($subtitles, 'transcript_color', null),
            'strokeColor' => Arr::get($subtitles, 'stroke_color', null),
            'strokeWidth' => null,
            'xAlignment' => Arr::get($subtitles, 'x_alignment', null),
            'yAlignment' => Arr::get($subtitles, 'y_alignment', null),
            'animationEffect' => Arr::get($subtitles, 'transcript_effect', null),
        ];

        $captions = collect($subtitles['transcript_source'])->map(function (array $caption) {
            $value = Arr::get($caption, 'value');
            $time = Arr::get($caption, 'time');
            $duration = Arr::get($caption, 'duration');

            $startTimeInMs = round($time * 1000, 6);
            $endTimeInMs = round(($time + $duration) * 1000, 6);
            $timestampMs = round(($startTimeInMs + $endTimeInMs) / 2, 6);

            return [
                'text' => ' '.$value,
                'startMs' => $startTimeInMs,
                'endMs' => $endTimeInMs,
                'confidence' => 1,
                'timestampMs' => $timestampMs,
            ];
        })->values();

        return array_merge($element, ['captions' => $captions->toArray()]);

    }

    private function extractSyllabyWatermark(Collection $elements): ?array
    {
        $watermark = $elements->filter(
            fn (array $element) => Arr::get($element, 'name') === 'syllaby-watermark'
        )->first();

        if (empty($watermark)) {
            return null;
        }

        return [
            'width' => Arr::get($watermark, 'width', 0),
            'height' => Arr::get($watermark, 'height', 0),
            'x' => Str::replace('vw', '%', Arr::get($watermark, 'x', '0%')),
            'y' => Str::replace('vh', '%', Arr::get($watermark, 'y', '0%')),
            'source' => Arr::get($watermark, 'source'),
            'time' => 0,
            'duration' => Arr::get($watermark, 'duration') ?? $this->duration,
            'id' => Arr::get($watermark, 'id'),
            'name' => Arr::get($watermark, 'name'),
            'type' => Arr::get($watermark, 'type'),
        ];
    }

    private function extractWatermark(Collection $elements): ?array
    {
        $watermark = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'image' && Str::startsWith(Arr::get($element, 'name'), 'watermark-');
        })->first();

        if (empty($watermark)) {
            return null;
        }

        $opacity = Arr::get($watermark, 'opacity', '100%');

        return [
            'width' => Arr::get($watermark, 'width', 0),
            'height' => Arr::get($watermark, 'height', 0),
            'x' => Arr::get($watermark, 'x', '0%'),
            'y' => Arr::get($watermark, 'y', '0%'),
            'opacity' => (float) (Str::before($opacity, '%') / 100),
            'source' => Arr::get($watermark, 'source'),
            'time' => 0,
            'duration' => Arr::get($watermark, 'duration') ?? $this->duration,
            'id' => Arr::get($watermark, 'id'),
            'name' => Arr::get($watermark, 'name'),
            'type' => Arr::get($watermark, 'type'),
        ];
    }

    private function extractOverlayEffect(Collection $elements): ?array
    {
        $overlay = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'composition' && Arr::get($element, 'name') === 'Overlay';
        })->first();

        if (empty($overlay)) {
            return null;
        }

        $overlay = Arr::get($overlay, 'elements.0');

        return [
            'id' => Arr::get($overlay, 'id'),
            'name' => Arr::get($overlay, 'name'),
            'time' => Arr::get($overlay, 'time', 0),
            'duration' => Arr::get($overlay, 'duration') ?? $this->duration,
            'type' => Arr::get($overlay, 'type'),
            'loop' => Arr::get($overlay, 'loop', true),
            'source' => Arr::get($overlay, 'source'),
            'blend_mode' => Arr::get($overlay, 'blend_mode'),
        ];
    }

    private function extractAnimationEffect(array $animation = [], bool $last = false): ?array
    {
        if (empty($animation) || $last) {
            return null;
        }

        $type = Arr::get($animation, 'type');

        [$direction, $duration] = match ($type) {
            'slide' => $this->resolveSlideDirection($animation),
            'scale' => $this->resolveScaleDirection($animation),
            default => [
                Arr::get($animation, 'type', 'fade'),
                (float) Arr::get($animation, 'duration', 0.6),
            ]
        };

        return ['type' => $direction, 'duration' => $duration];
    }

    private function extractSoundEffect(Collection $elements): ?array
    {
        $sfx = $elements->filter(function (array $element) {
            return Arr::get($element, 'type') === 'composition' && Arr::get($element, 'elements.1.name') === 'sfx';
        })->first();

        if (empty($sfx)) {
            return null;
        }

        return [
            'source' => Arr::get($sfx, 'elements.1.source'),
            'time' => Arr::get($sfx, 'elements.1.time') ?? 0,
            'duration' => Arr::get($sfx, 'elements.1.duration') ?? 1,
            'id' => Arr::get($sfx, 'elements.1.id'),
            'name' => Arr::get($sfx, 'elements.1.name'),
            'type' => Arr::get($sfx, 'elements.1.type'),
            'volume' => 0.7,
            'playbackRate' => $this->getPlaybackRateFromPercentage(Arr::get($sfx, 'elements.1.speed', '100%')),
            'trimStart' => Arr::get($sfx, 'elements.1.trim_start', 0),
        ];
    }

    private function getVolumeFromPercentage(string $percentage): float
    {
        return $percentage ? (float) $percentage / 100 : 1;
    }

    private function getPlaybackRateFromPercentage(string $percentage): float
    {
        if (empty($percentage)) {
            return 1;
        }

        return (float) $percentage / 100;
    }

    private function resolveSlideDirection(array $animation = []): array
    {
        $duration = Arr::get($animation, 'duration', 0.5);
        $direction = Arr::get($animation, 'direction');

        $degrees = (int) str_replace('°', '', $direction);
        $normalizedDegrees = (($degrees % 360) + 360) % 360;

        $direction = match (true) {
            $normalizedDegrees >= 315 || $normalizedDegrees < 45 => 'slideRight',
            $normalizedDegrees >= 45 && $normalizedDegrees < 135 => 'slideUp',
            $normalizedDegrees >= 135 && $normalizedDegrees < 225 => 'slideLeft',
            default => 'slideDown'
        };

        return [$direction, $duration];
    }

    private function resolveScaleDirection(array $animation = []): ?array
    {
        $x = Arr::get($animation, 'x_anchor', '50%');
        $y = Arr::get($animation, 'y_anchor', '50%');
        $duration = Arr::get($animation, 'duration', 0.5);

        $direction = match (true) {
            $x === '50%' && $y === '50%' => 'scale',
            $x === '100%' && $y === '50%' => 'scaleLeft',
            $x === '0%' && $y === '50%' => 'scaleRight',
            $x === '50%' && $y === '100%' => 'scaleUp',
            $x === '50%' && $y === '0%' => 'scaleDown',
            default => 'scale',
        };

        return [$direction, $duration];
    }
}
