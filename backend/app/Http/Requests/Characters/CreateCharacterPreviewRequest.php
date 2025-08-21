<?php

namespace App\Http\Requests\Characters;

use Illuminate\Validation\Rule;
use App\Syllaby\Characters\Character;
use Illuminate\Foundation\Http\FormRequest;

class CreateCharacterPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Character $character */
        $character = $this->route('character');

        return $this->user()->owns($character);
    }

    public function rules(): array
    {
        return [
            'genre_id' => [
                'required',
                'integer',
                Rule::exists('genres', 'id'),
            ],
            'image_id' => [
                'required',
                'integer',
                Rule::exists('media', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('collection_name', 'reference'),
            ],
        ];
    }
}
