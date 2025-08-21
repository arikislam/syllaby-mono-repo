<?php

namespace App\Http\Requests\Animation;

use App\Syllaby\Videos\Faceless;
use Illuminate\Foundation\Http\FormRequest;

class BulkAnimationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Faceless $faceless */
        $faceless = $this->route('faceless');

        return $this->user()->owns($faceless);
    }

    public function rules(): array
    {
        return [
            'assets' => ['required', 'array', 'min:1'],
            'assets.*' => ['required', 'integer'],
        ];
    }
}
