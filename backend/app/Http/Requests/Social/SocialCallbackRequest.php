<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class SocialCallbackRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->query('code'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
