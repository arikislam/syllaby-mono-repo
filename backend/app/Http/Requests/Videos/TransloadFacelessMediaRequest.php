<?php

namespace App\Http\Requests\Videos;

use Arr;
use Illuminate\Validation\Validator;
use App\Http\Requests\Assets\TransloadMediaRequest;

class TransloadFacelessMediaRequest extends TransloadMediaRequest
{
    const int ALLOWED_SIZE = 50 * 1024 * 1024; // 50MB

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'index' => ['required', 'integer', 'min:0'],
        ]);
    }

    public function with(): array
    {
        return [
            function (Validator $validator) {
                if ($this->isGreaterThanAllowed()) {
                    $validator->errors()->add('url', 'The file size exceeds the allowed limit.');
                }
            },
        ];
    }

    private function isGreaterThanAllowed(): bool
    {
        return Arr::get($this->details, 'size', PHP_INT_MAX) > self::ALLOWED_SIZE;
    }
}
