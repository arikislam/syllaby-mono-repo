<?php

namespace App\Syllaby\Assets\Listeners;

use Throwable;
use FFMpeg\FFProbe;
use Carbon\CarbonInterval;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Enums\Dimension;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/** @see https://cloudinary.com/guides/video-formats/webm-format-what-you-should-know */
class StoreMediaMetadata
{
    protected array $defaults = [
        'width' => 0,
        'height' => 0,
        'codec' => 'unknown',
        'aspect_ratio' => '0:0',
        'frame_rate' => 0,
        'resolution' => '0x0',
        'duration' => 0.0,
        'orientation' => 'unknown',
    ];

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        if ($this->isImage($event->media)) {
            $this->storeImageProperties($event->media);

            return;
        }

        $ffprobe = FFProbe::create([
            'ffprobe.binaries' => config('media-library.ffprobe_path'),
        ]);

        $source = $event->media->getFullUrl();

        if (! $ffprobe->isValid($source)) {
            return;
        }

        try {
            $info = $this->isAudio($event->media) ? $ffprobe->streams($source)->audios()->first() : $ffprobe->streams($source)->videos()->first();

            $width = $this->isAudio($event->media) ? 0 : $info->getDimensions()->getWidth() ?? 0;
            $height = $this->isAudio($event->media) ? 0 : $info->getDimensions()->getHeight() ?? 0;

            $event->media->setCustomProperty('width', $width)
                ->setCustomProperty('height', $height)
                ->setCustomProperty('codec', $info->get('codec_name'))
                ->setCustomProperty('aspect_ratio', $this->calculateAspectRatio($width, $height))
                ->setCustomProperty('frame_rate', $this->formatFrameRate($info->get('r_frame_rate')))
                ->setCustomProperty('resolution', "{$width}x{$height}")
                ->setCustomProperty('duration', $this->resolveDuration($info, $event->media))
                ->setCustomProperty('orientation', $this->orientation($width, $height))
                ->save();
        } catch (Throwable) {
            $this->setDefaultProperties($event->media);
        }
    }

    /**
     * Check if given media instance is an image.
     */
    private function isImage(Media $media): bool
    {
        return Str::startsWith($media->mime_type, 'image/');
    }

    /**
     * Calculate the aspect ratio of the video.
     */
    private function calculateAspectRatio(int $width, int $height): string
    {
        if ($width === 0 || $height === 0) {
            return '0:0';
        }

        $divisor = $this->gcd($width, $height);

        return sprintf('%d:%d', $width / $divisor, $height / $divisor);
    }

    /**
     * Calculate the greatest common divisor (HCF) of width and height.
     */
    private function gcd(int $width, int $height): int
    {
        while ($height !== 0) {
            $t = $height;
            $height = $width % $height;
            $width = $t;
        }

        return $width;
    }

    /**
     * Format the frame rate in `x` fps (frames per second) from "24/1" format.
     */
    private function formatFrameRate(string $frames): int
    {
        [$numerator, $denominator] = explode('/', $frames);

        if (intval($denominator) === 0) {
            return 0;
        }

        return (int) $numerator / (int) $denominator;
    }

    /**
     * Determine the orientation of the video.
     */
    private function orientation(int $width, int $height): string
    {
        return match (true) {
            $width === 0 || $height === 0 => 'none',
            $width > $height => Dimension::LANDSCAPE->value,
            $width < $height => Dimension::PORTRAIT->value,
            default => Dimension::SQUARE->value
        };
    }

    /**
     * Check if given media instance is an audio.
     */
    private function isAudio(Media $media): bool
    {
        return Str::startsWith($media->mime_type, 'audio/');
    }

    /**
     * Set default properties for the media instance in case if ffmpeg fails.
     */
    private function setDefaultProperties(Media $media): void
    {
        foreach ($this->defaults as $key => $value) {
            $media->setCustomProperty($key, $value);
        }

        $media->save();
    }

    /**
     * Resolve the duration of the media based on mime type.
     */
    private function resolveDuration(?FFProbe\DataMapping\Stream $stream, Media $media): float
    {
        if ($this->isWebm($media)) {
            return $this->formatWebmDuration(Arr::get($stream->get('tags'), 'DURATION'));
        }

        return (float) $stream->get('duration');
    }

    /**
     * Convert the duration to seconds.
     *  - 00:00:18.047000000 => 18.047
     */
    private function formatWebmDuration(?string $duration): float
    {
        if (! $duration) {
            return 0.0;
        }

        $time = explode(':', $duration);

        return CarbonInterval::create(0, 0, 0, 0, $time[0], $time[1], $time[2])->totalSeconds;
    }

    /**
     * Check if given media instance is a web (video or audio).
     */
    private function isWebm(Media $media): bool
    {
        return Str::endsWith($media->mime_type, 'webm');
    }

    /**
     * Store image properties as custom properties.
     */
    private function storeImageProperties(Media $media): void
    {
        $image = getimagesize($media->getFullUrl());

        if (! $image) {
            $image = [0, 0];
        }

        $media->setCustomProperty('width', $width = $image[0])
            ->setCustomProperty('height', $height = $image[1])
            ->setCustomProperty('aspect_ratio', $this->calculateAspectRatio($width, $height))
            ->setCustomProperty('resolution', "{$width}x{$height}")
            ->setCustomProperty('orientation', $this->orientation($width, $height))
            ->save();
    }
}
