<?php

namespace App\Http\Requests\RealClones;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\RealClones\Rules\ImageHasFace;
use App\Syllaby\RealClones\Enums\RealCloneProvider;

class CreatePhotoAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'face' => ['nullable', 'array'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'provider' => ['required', 'string', Rule::in([RealCloneProvider::D_ID->value])],
            'file' => [
                'bail',
                'required',
                File::image()->max('10mb')->types(['png', 'jpg'])->dimensions(
                    Rule::dimensions()->minWidth(500)->minHeight(500)
                ),
                new ImageHasFace($this),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'file.mimes' => __('The file type must be either .png or .jpg'),
        ];
    }
}
