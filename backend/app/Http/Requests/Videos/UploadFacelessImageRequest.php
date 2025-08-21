<?php

namespace App\Http\Requests\Videos;

use App\Http\Requests\Assets\UploadAssetRequest;

class UploadFacelessImageRequest extends UploadAssetRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'index' => ['required', 'integer', 'min:0'],
        ]);
    }
}
