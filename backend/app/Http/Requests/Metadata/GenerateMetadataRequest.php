<?php

namespace App\Http\Requests\Metadata;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @property string $provider
 * @property string $context
 */
class GenerateMetadataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(SocialAccountEnum::channels())],
            'context' => ['required', 'string'],
        ];
    }
}
