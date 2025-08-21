<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @property-read string $provider
 */
class DestroyThumbnailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $this->merge(['provider' => $this->query('provider')]);

        return $this->user()->can('delete', $this->route('publication'));
    }

    public function rules(): array
    {
        return [
            'provider' => [
                'required', 'string',
                Rule::in([SocialAccountEnum::Youtube->toString(), SocialAccountEnum::LinkedIn->toString(), SocialAccountEnum::Facebook->toString()])
            ]
        ];
    }
}
