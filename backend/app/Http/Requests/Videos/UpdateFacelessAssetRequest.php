<?php

namespace App\Http\Requests\Videos;

use Illuminate\Validation\Rule;
use App\Syllaby\Videos\Faceless;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Container\Attributes\RouteParameter;

class UpdateFacelessAssetRequest extends FormRequest
{
    public function authorize(#[RouteParameter('faceless')] Faceless $faceless): bool
    {
        return $this->user()->can('update', $faceless);
    }

    public function rules(): array
    {
        return [
            'index' => ['required', 'integer', 'min:0'],
            'id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')->where('user_id', $this->user()->id), // Temporary condition till we allow cross user asset sharing
            ],
        ];
    }
}
