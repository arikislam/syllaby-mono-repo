<?php

namespace App\Syllaby\Publisher\Publications\Concerns;

use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Publisher\Publications\Publication;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @see https://support.buffer.com/article/555-using-facebook-with-buffer for video requirements
 * @see https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#creating for Instagram video requirements
 * @see https://developers.facebook.com/docs/threads/overview/ for threads
 */
trait ValidatesMetaMedia
{
    public function isThreadsPost(Media $media): bool
    {
        return $this->getDuration($media) <= 300 // 5 minutes
            && $this->getSize($media) <= 1024 * 1024 * 1024 // 1GB
            && ($this->getFrameRate($media) >= 23 && $this->getFrameRate($media) <= 60)
            && in_array($this->getMimeType($media), ['video/mp4', 'video/quicktime'])
            && $this->getResolutionWidth($media) <= 1920;
    }

    public function isInstaReel(Media $media): bool
    {
        return ($this->getDuration($media) >= 3 && $this->getDuration($media) <= 15 * 60) // 15 minutes
            && $this->getSize($media) <= static::FILE_SIZE
            && ($this->getFrameRate($media) >= 23 && $this->getFrameRate($media) <= 60)
            && in_array($this->getMimeType($media), ['video/mp4', 'video/quicktime'])
            && $this->getResolutionWidth($media) <= 1920;
     }

     public function isInstaStory(Media $media): bool
     {
         return ($this->getDuration($media) >= 3 && $this->getDuration($media) <= 60)
             && $this->getSize($media) <= 100 * 1024 * 1024 // 100MB
             && ($this->getFrameRate($media) >= 23 && $this->getFrameRate($media) <= 60)
             && in_array($this->getMimeType($media), ['video/mp4', 'video/quicktime'])
             && $this->getResolutionWidth($media) <= 1920;
     }

    public function isFacebookVideoPost(Media $media): bool
    {
        return $this->getSize($media) <= static::FILE_SIZE
            && $this->getDuration($media) <= 60 * 20 // 20 minutes
            && ($this->getFrameRate($media) >= 24 && $this->getFrameRate($media) <= 60)
            && in_array($this->getMimeType($media), $this->getAllowedVideoMimes());
    }

    public function isFacebookReel(Media $media): bool
    {
        return $this->isPortrait($media)
            && $this->getSize($media) <= static::FILE_SIZE
            && $this->getMimeType($media) === 'video/mp4'
            && ($this->getFrameRate($media) >= 24 && $this->getFrameRate($media) <= 60)
            && ($this->getDuration($media) >= 3 && $this->getDuration($media) <= 90)
            && ($this->getResolutionWidth($media) >= 540 && $this->getResolutionHeight($media) >= 960);
    }

    public function isFacebookStory(Media $media): bool
    {
        return $this->isPortrait($media)
            && $this->getSize($media) <= static::FILE_SIZE
            && $this->getMimeType($media) === 'video/mp4'
            && ($this->getFrameRate($media) >= 24 && $this->getFrameRate($media) <= 60)
            && ($this->getDuration($media) >= 3 && $this->getDuration($media) < 60)
            && ($this->getResolutionWidth($media) >= 540 && $this->getResolutionHeight($media) >= 960);
    }

    public function hasValidFacebookThumbnail(Publication $publication): bool
    {
        if (! $this->hasThumbnail($publication)) {
            return true;
        }

        $thumbnail = $publication->thumbnail(SocialAccountEnum::Facebook);

        return $this->getSize($thumbnail) <= 10 * 1024 * 1024 && in_array($thumbnail->mime_type, $this->getAllowedThumbnailMimes());
    }

    public function getMimeType(Media $media): string
    {
        return $media->mime_type;
    }

    public function getWidth(Media $media): int
    {
        return (int) $media->getCustomProperty('width');
    }

    public function getHeight(Media $media): int
    {
        return (int) $media->getCustomProperty('height');
    }

    public function getCodecName(Media $media): string
    {
        return $media->getCustomProperty('codec');
    }

    public function getAspectRatio(Media $media): string
    {
        return $media->getCustomProperty('aspect_ratio');
    }

    public function getFrameRate(Media $media): int
    {
        return (int) $media->getCustomProperty('frame_rate');
    }

    public function getResolution(Media $media): string
    {
        return $media->getCustomProperty('resolution', '0x0');
    }

    public function getDuration(Media $media): float
    {
        return (float) $media->getCustomProperty('duration');
    }

    public function isLandscape(Media $media): bool
    {
        return $media->getCustomProperty('orientation') === Dimension::LANDSCAPE->value;
    }

    public function isPortrait(Media $media): bool
    {
        return $media->getCustomProperty('orientation') === Dimension::PORTRAIT->value;
    }

    public function getResolutionWidth(Media $media): int
    {
        return (int) explode('x', $this->getResolution($media))[0];
    }

    public function getResolutionHeight(Media $media): int
    {
        return (int) explode('x', $this->getResolution($media))[1];
    }

    public function getAllowedVideoMimes(): array
    {
        return ['video/avi', 'video/x-msvideo', 'video/quicktime', 'video/mp4', 'video/x-m4v'];
    }

    public function getAllowedThumbnailMimes(): array
    {
        return ['image/bmp', 'image/gif', 'image/jpeg', 'image/png', 'image/tiff', 'image/x-ms-bmp'];
    }

    public function hasThumbnail(Publication $publication): bool
    {
        return $publication->thumbnail(SocialAccountEnum::Facebook) !== null;
    }

    public function getSize(Media $media): int
    {
        return $media->size;
    }
}