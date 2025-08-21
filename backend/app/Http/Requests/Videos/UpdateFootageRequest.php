<?php

namespace App\Http\Requests\Videos;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFootageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('update', $this->route('footage'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'width' => ['required', 'integer'],
            'height' => ['required', 'integer'],
        ];
    }
}
