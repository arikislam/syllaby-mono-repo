<?php

namespace App\Http\Requests\Assets;

use App\Syllaby\Assets\Media;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    protected array $types = [
        'mp3', 'wav',
        'webm', 'mov', 'mp4',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        if (! $this->has('files') || ! is_array($this->file('files'))) {
            return Response::deny('A file is required')->withStatus(422);
        }

        $size = array_reduce($this->file('files'), function ($total, $file) {
            return $total + $file->getSize();
        });

        return $gate->inspect('upload', [Media::class, $size]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.*' => File::types($this->types)->min('1kb')->max('200mb'),
        ];
    }
}
