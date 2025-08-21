<?php

namespace App\Http\Requests\Credit;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $type
 * @property-read string $script
 * @property-read string $provider
 */
class EstimateCreditRequest extends FormRequest
{
    private array $types = [
        'idea', 'script', 'speech', 'video', 'faceless', 'real_clone',
    ];

    private array $providers = [
        'd-id', 'heygen', 'fastvideo', 'creatomate',
    ];

    public function rules(): array
    {
        return [
            'has_genre' => ['required_if:type,faceless', 'boolean'],
            'type' => ['required', 'string', Rule::in($this->types)],
            'script' => ['required_if:type,faceless', 'string'],
            'provider' => ['required_if:type,video,real_clone,faceless', 'string', Rule::in($this->providers)],
        ];
    }
}
