<?php

namespace App\Http\Requests\Assets;

use Illuminate\Support\Str;
use App\Syllaby\Assets\Media;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UploadAssetRequest extends FormRequest
{
    private array $images = ['jpg', 'jpeg', 'png', 'webp'];

    private array $videos = ['mp4'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        if (! $this->has('file')) {
            return Response::deny('A file is required')->withStatus(422);
        }

        return $gate->inspect('upload', [Media::class, $this->file('file')->getSize()]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', Rule::exists('genres', 'id')],
            'file' => [
                'required',
                File::types([...$this->images, ...$this->videos]),
                Rule::when(
                    $this->isImage(),
                    fn () => File::image()->types($this->images)->max('5mb'),
                    fn () => File::types($this->videos)->max('50mb')
                ),
            ],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        $custom = [];

        if ($this->isImage()) {
            $custom = ['file.max' => 'Please upload an image (JPEG, PNG, WEBP - max 5 MB)'];
        }

        if ($this->isVideo()) {
            $custom = ['file.max' => 'Please upload a video (MP4 - max 50 MB)'];
        }

        return array_merge(parent::messages(), $custom);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        $extension = Str::lower($this->file('file')->getClientOriginalExtension());

        return in_array($extension, $this->images);
    }

    /**
     * Check if the file is a video.
     */
    public function isVideo(): bool
    {
        $extension = Str::lower($this->file('file')->getClientOriginalExtension());

        return in_array($extension, $this->videos);
    }
}
