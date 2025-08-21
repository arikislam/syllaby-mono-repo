<?php

namespace App\Syllaby\RealClones\Rules;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Client\RequestException;
use App\Syllaby\RealClones\Vendors\Presenter;
use Intervention\Image\Image as ImageBuilder;
use Illuminate\Contracts\Validation\DataAwareRule;
use App\Syllaby\RealClones\Enums\RealCloneProvider;
use Illuminate\Contracts\Validation\ValidationRule;

class ImageHasFace implements DataAwareRule, ValidationRule
{
    /**
     * D-ID allowed maximum resolution (width * height)
     */
    const int MAX_RESOLUTION = 16000000;

    /**
     * All the data under validation.
     */
    protected array $data = [];

    /**
     * Validation messages for different fail reasons.
     */
    protected array $messages = [
        'NO_FACE_DETECTED' => 'No face detected. Please upload a different image',
        'LOW_CONFIDENCE' => 'Face not clear. Ensure good lighting, no coverings, and mouth is visible',
    ];

    public function __construct(protected Request $request) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $file = $this->ensureImageResolution($value);
        Arr::set($this->data, $attribute, $file);

        try {
            $provider = Arr::get($this->data, 'provider', RealCloneProvider::D_ID->value);

            if (! $face = Presenter::driver($provider)->detectFaces($file)) {
                $fail(Arr::get($this->messages, 'NO_FACE_DETECTED'));

                return;
            }

            if (! $this->hasUsableFace($face)) {
                $fail(Arr::get($this->messages, 'LOW_CONFIDENCE'));

                return;
            }

            $this->request->merge(['face' => $face]);
        } catch (RequestException $exception) {
            $fail(Arr::get($this->messages, 'NO_FACE_DETECTED'));
        }
    }

    /**
     * Set the data under validation.
     */
    public function setData(array $data): static
    {
        return tap($this, fn () => $this->data = $data);
    }

    /**
     * Check if the face detected is not occluded and can be used.
     */
    protected function hasUsableFace(array $face): bool
    {
        $hasFaceDetected = Arr::get($face, 'detect_confidence', 0) > 90;
        $hasVisibleFace = Arr::get($face, 'face_occluded', true) === false;
        $visibilityConfidence = Arr::get($face, 'face_occluded_confidence', 0) > 90;

        return $hasVisibleFace && $hasFaceDetected && $visibilityConfidence;
    }

    /**
     * Replaces the original uploaded file when too large.
     */
    protected function ensureImageResolution(UploadedFile $file): UploadedFile
    {
        $image = Image::make($file->getContent());

        if (($image->width() * $image->height()) < self::MAX_RESOLUTION) {
            return $file;
        }

        $image = match (true) {
            $image->width() > $image->height() => $this->resize($image, width: 4000, height: null),
            default => $this->resize($image, width: null, height: 4000),
        };

        file_put_contents($file->getRealPath(), $image->encode($file->getClientOriginalExtension(), 95));

        return $file;
    }

    /**
     * Resize the image to a sensible default below 16MB resolution.
     */
    protected function resize(ImageBuilder $image, ?int $width, ?int $height): ImageBuilder
    {
        return $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }
}
