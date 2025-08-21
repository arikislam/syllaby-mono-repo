<?php

namespace App\Http\Requests\Videos;

use Illuminate\Validation\Rules\File;
use App\Syllaby\Assets\Rules\AudioDuration;
use App\Http\Requests\Assets\UploadMediaRequest;

class UploadAudioRequest extends UploadMediaRequest
{
    protected array $types = [
        'mp3', 'wav',
    ];

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        return array_merge($rules, [
            'files' => ['required', 'array', 'min:1', 'max:1'],
            'files.*' => ['required', 'bail', File::types($this->types)->min('1kb')->max('130mb'), new AudioDuration(1800)],
        ]);
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'files.*.mimes' => 'The file must be: '.implode(', ', $this->types),
        ];
    }
}
