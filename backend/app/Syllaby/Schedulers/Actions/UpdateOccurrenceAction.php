<?php

namespace App\Syllaby\Schedulers\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Schedulers\Occurrence;

class UpdateOccurrenceAction
{
    /**
     * Handle the update of an occurrence.
     */
    public function handle(Occurrence $occurrence, array $input): Occurrence
    {
        return tap($occurrence)->update([
            'topic' => Arr::get($input, 'topic', $occurrence->topic),
            'script' => Arr::get($input, 'script', $occurrence->script),
        ]);
    }
}
