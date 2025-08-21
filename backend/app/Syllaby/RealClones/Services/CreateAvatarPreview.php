<?php

namespace App\Syllaby\RealClones\Services;

use FFMpeg\FFMpeg;
use Illuminate\Support\Str;
use FFMpeg\Coordinate\TimeCode;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;

class CreateAvatarPreview
{
    /**
     * Creates am avatar preview from a sample video and caches it on model.
     */
    public static function from(string $source, Avatar $avatar): void
    {
        Storage::disk('local')->makeDirectory("avatars/{$avatar->id}");

        try {
            $thumbnail = static::extractFrame($source, $avatar);
            $path = Storage::disk('local')->path("avatars/{$avatar->id}/{$thumbnail}");

            Image::make($path)->resize(1080, 1080, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($path, quality: 85);

            $media = $avatar->addMedia($path)
                ->addCustomHeaders(['ACL' => 'public-read'])
                ->withCustomProperties(['user_id' => $avatar->user_id])
                ->toMediaCollection('preview');

            $avatar->update(['preview_url' => $media->getFullUrl()]);
        } catch (FileDoesNotExist|FileIsTooBig $exception) {
            Log::error($exception->getMessage());
        } finally {
            Storage::disk('local')->deleteDirectory("avatars/{$avatar->id}");
        }
    }

    /**
     * Extracts a preview from the source video
     */
    private static function extractFrame(string $source, Avatar $avatar): string
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('media-library.ffmpeg_path'),
        ]);

        $folder = Storage::disk('local')->path("avatars/{$avatar->id}");

        return tap(Str::uuid().'.png', function ($filename) use ($ffmpeg, $source, $folder) {
            $frame = $ffmpeg->open($source)->frame(TimeCode::fromSeconds(4));
            $frame->save("{$folder}/{$filename}");
        });
    }
}
