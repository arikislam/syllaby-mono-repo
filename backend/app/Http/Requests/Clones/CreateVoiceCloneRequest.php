<?php

namespace App\Http\Requests\Clones;

use App\Syllaby\Speeches\Voice;
use Illuminate\Validation\Rule;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Relations\Relation;

class CreateVoiceCloneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('create', [Clonable::class, 'max_voice_clones', Relation::getMorphAlias(Voice::class)]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'terms' => ['required', 'accepted'],
            'name' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:200'],
            'provider' => ['required', 'string', Rule::in(['elevenlabs'])],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'samples' => ['required', 'array', 'max:3'],
            'samples.*' => ['required', 'file', File::types(['mp3', 'webm', 'mp4'])->max('10mb')],
        ];
    }
}
