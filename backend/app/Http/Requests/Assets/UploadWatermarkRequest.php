<?php

namespace App\Http\Requests\Assets;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UploadWatermarkRequest extends UploadMediaRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'files.*' => File::image()
                ->min('1kb')
                ->max('1mb')
                ->dimensions(Rule::dimensions()->maxWidth(150)->maxHeight(150)),
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'files.*.dimensions' => 'The file must be 150x150 pixels.',
        ]);
    }
}
