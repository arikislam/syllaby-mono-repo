<?php

namespace App\Http\Requests\Schedulers;

use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Schedulers\Rules\SchedulerCsvSchema;

class CsvParserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048', new SchedulerCsvSchema],
        ];
    }
}
