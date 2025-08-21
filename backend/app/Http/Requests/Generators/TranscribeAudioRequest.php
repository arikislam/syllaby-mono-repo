<?php

namespace App\Http\Requests\Generators;

use App\Syllaby\Assets\Media;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class TranscribeAudioRequest extends FormRequest
{
    /**
     * The audio file to be transcribed.
     */
    public Media $audio;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $action = CreditEventEnum::AUDIO_TRANSCRIPTION;
        $duration = ceil($this->audio->getCustomProperty('duration') / 60);

        $credits = $gate->inspect('generate', [Generator::class, $action, $duration]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('update', $this->route('faceless')),
        };
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'audio_id' => ['required', Rule::exists('media', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->audio = $this->route('faceless')->getFirstMedia('script');

        $this->merge(['audio_id' => $this->audio->id]);
    }
}
