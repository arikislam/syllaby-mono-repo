<?php

namespace App\Http\Requests\Characters;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Characters\Character;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class StartCharacterTrainingRequest extends FormRequest
{
    public function authorize(Gate $gate): Response
    {
        /** @var Character $character */
        $character = $this->route('character');

        return $gate->inspect('train', [Character::class, $character]);
    }

    public function rules(): array
    {
        return [
            'preview_id' => [
                'required',
                'integer',
                Rule::exists('media', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('collection_name', 'sandbox'),
            ],
        ];
    }
}
