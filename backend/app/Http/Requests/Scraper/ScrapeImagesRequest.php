<?php

namespace App\Http\Requests\Scraper;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ScrapeImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('faceless'));
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
        ];
    }
}
