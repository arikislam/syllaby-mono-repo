<?php

namespace App\Http\Requests\Characters;

use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Characters\Character;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterRequest extends FormRequest
{
    public function authorize(Gate $gate): Response
    {
        /** @var Character $character */
        $character = $this->route('character');

        return $gate->inspect('update', [Character::class, $character]);
    }

    public function rules(): array
    {
        return [
            'genre_id' => ['nullable', 'integer', Rule::exists('genres', 'id')],
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
            'traits' => ['nullable', 'array'],
            'age' => ['nullable', 'string'],
        ];
    }
}
