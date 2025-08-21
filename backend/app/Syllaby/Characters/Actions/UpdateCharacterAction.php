<?php

namespace App\Syllaby\Characters\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Characters\Character;
use App\Syllaby\Assets\Actions\UploadMediaAction;

class UpdateCharacterAction
{
    public function __construct(protected UploadMediaAction $upload) {}

    public function handle(Character $character, array $input): Character
    {
        $character->update([
            'genre_id' => Arr::get($input, 'genre_id', $character->genre_id),
            'name' => Arr::get($input, 'name', $character->name),
            'description' => Arr::get($input, 'description', $character->description),
            'gender' => Arr::get($input, 'gender', $character->gender),
            'meta->traits' => Arr::get($input, 'traits', $character->meta->traits ?? []),
            'meta->age' => Arr::get($input, 'age', $character->meta->age ?? null),
        ]);

        if (Arr::has($input, 'image')) {
            $this->upload->handle($character, Arr::wrap($input['image']), 'reference');
        }

        return $character;
    }
}
