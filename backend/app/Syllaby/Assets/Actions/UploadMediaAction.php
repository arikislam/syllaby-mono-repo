<?php

namespace App\Syllaby\Assets\Actions;

use Arr;
use Exception;
use Throwable;
use App\Syllaby\Assets\Asset;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;

class UploadMediaAction
{
    /**
     * Uploads the given file and associates it to the current model.
     *
     * @throws Exception
     */
    public function handle(HasMedia $model, array $files, string $collection = 'default', array $properties = [], int $order = 1): array
    {
        return collect($files)->map(function ($file) use ($model, $collection, $properties, $order) {
            return $this->attach($model, $file, $collection, $properties, $order);
        })->all();
    }

    /**@throws FileDoesNotExist|FileIsTooBig|Throwable */
    private function attach(HasMedia $model, UploadedFile $file, string $collection, array $properties, int $order): Media
    {
        $headers = Arr::only(config('media-library.remote.extra_headers'), 'ACL');

        try {
            return retry(5, fn () => $model->addMedia($file)
                ->addCustomHeaders($headers)
                ->withCustomProperties($properties)
                ->withAttributes(['user_id' => $model->user_id])
                ->setOrder($order)
                ->toMediaCollection($collection), 2000);
        } catch (FileIsTooBig|FileDoesNotExist $exception) {
            Log::alert($exception->getMessage());
            throw $exception;
        } catch (Throwable $exception) {
            Log::alert('Internal Server Error. Reason: {reason}', ['reason' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
