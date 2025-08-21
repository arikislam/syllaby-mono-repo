<?php

namespace App\Syllaby\Assets\Actions;

use Exception;
use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Log;

class TransloadMediaAction
{
    /**
     * Uploads a file from the given URL.
     *
     * @throws Throwable
     */
    public function handle(HasMedia $model, string $url, string $collection = 'default', array $properties = [], int $order = 1): Media
    {
        $headers = Arr::only(config('media-library.remote.extra_headers'), 'ACL');

        try {
            return retry(6, fn () => $model->addMediaFromUrl($url)
                ->setOrder($order)
                ->addCustomHeaders($headers)
                ->withCustomProperties($properties)
                ->withAttributes(['user_id' => $model->user_id])
                ->toMediaCollection($collection), 2000);
        } catch (Exception $exception) {
            Log::error('Error uploading file: {reason}', ['reason' => $exception->getMessage()]);

            throw $exception;
        }
    }
}
