<?php

namespace App\Http\Requests\Scraper;

use App\System\Traits\HandlesThrottling;
use App\Syllaby\Scraper\Rules\SupportedUrl;
use Illuminate\Foundation\Http\FormRequest;

class ScrapeUrlRequest extends FormRequest
{
    use HandlesThrottling;

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', new SupportedUrl],
            'duration' => ['required', 'integer'],
            'style' => ['required', 'string'],
            'language' => ['required', 'string'],
            'tone' => ['required', 'string'],
        ];
    }
}
