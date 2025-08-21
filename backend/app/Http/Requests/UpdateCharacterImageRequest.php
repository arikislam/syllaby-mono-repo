<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use App\Syllaby\Characters\Character;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterImageRequest extends FormRequest
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
            'image' => [
                'required',
                'file',
                File::image()->max('10mb'),
            ],
        ];
    }
}
