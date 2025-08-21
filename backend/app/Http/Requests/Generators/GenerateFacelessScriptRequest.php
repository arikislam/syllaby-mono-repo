<?php

namespace App\Http\Requests\Generators;

class GenerateFacelessScriptRequest extends GenerateScriptRequest
{
    public function rules(): array
    {
        return [
            'style' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:500'],
            'duration' => ['required', 'integer'],
        ];
    }
}
