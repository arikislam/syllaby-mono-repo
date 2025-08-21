<?php

namespace App\Syllaby\Clonables\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Clonables\Clonable;

class UpdateVoiceCloneAction
{
    /**
     * Saves data to be used in voice creation.
     */
    public function handle(Clonable $clonable, array $input): Clonable
    {
        $clonable->model->update([
            'name' => Arr::get($input, 'name'),
            'gender' => Arr::get($input, 'gender'),
        ]);

        return tap($clonable)->update([
            'metadata' => Arr::only($input, 'description'),
        ]);
    }
}
