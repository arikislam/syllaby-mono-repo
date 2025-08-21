<?php

namespace App\Syllaby\Characters\Actions;

use Arr;
use Str;
use App\Syllaby\Users\User;
use App\Syllaby\Characters\Character;
use App\Syllaby\Assets\Actions\UploadMediaAction;

class CreateCharacterAction
{
    public function __construct(protected UploadMediaAction $upload) {}

    public function handle(User $user, array $input): Character
    {
        $character = $user->characters()->create([
            'genre_id' => Arr::get($input, 'genre_id'),
            'name' => $name = Arr::get($input, 'name', "{$user->name} - Character"),
            'slug' => str($name)->slug()->append('-'.Str::random(4))->toString(),
            'description' => Arr::get($input, 'description'),
            'gender' => Arr::get($input, 'gender', 'unknown'),
            'meta->traits' => Arr::get($input, 'traits', []),
            'meta->age' => Arr::get($input, 'age'),
        ]);

        $this->upload->handle($character, Arr::wrap($input['image']), 'reference');

        return $character;
    }
}
