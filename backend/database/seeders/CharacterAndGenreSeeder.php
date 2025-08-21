<?php

namespace Database\Seeders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Syllaby\Characters\Genre;
use Illuminate\Support\Facades\File;
use App\Syllaby\Characters\Character;
use App\Syllaby\Characters\Characters;
use App\Syllaby\Videos\Enums\StoryGenre;
use App\Syllaby\Characters\Enums\CharacterStatus;

class CharacterAndGenreSeeder extends Seeder
{
    protected static array $files = [
        'genres' => 'data/characters/genres.json',
        'characters' => 'data/characters/characters.json',
    ];

    public function run(): void
    {
        $this->seedGenres();

        $this->seedCharacters();
    }

    private function seedGenres(): void
    {
        $this->command->info('Seeding genres...');

        $genres = [];

        foreach (StoryGenre::cases() as $genre) {
            $insertion = [
                'name' => Arr::get(StoryGenre::all(), sprintf('%s.name', $genre->value), $genre->value),
                'slug' => $genre->value,
                'details' => $genre->details(),
                'active' => true,
                'prompt' => $genre->genrePrompt(),
                'consistent_character' => Arr::get(StoryGenre::all(), sprintf('%s.character_consistency', $genre->value), true),
                'meta' => StoryGenre::prompt($genre, '[PROMPT]'),
            ];

            $genres[] = $insertion;

            Genre::updateOrCreate(['slug' => $genre->value], $insertion);
        }

        File::put(storage_path(self::$files['genres']), json_encode($genres, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->command->info('Genres seeded successfully. Total Genres: '.Genre::count());
        $this->command->info('==========================================');
    }

    private function seedCharacters(): void
    {
        $this->command->newLine();
        $this->command->info('Seeding CHARACTERS...');

        $genres = Genre::all()->keyBy('slug');
        $characters = [];

        foreach (Characters::cases() as $character) {
            foreach (Arr::get($character->details(), 'genres', []) as $slug => $genre) {
                if (! $genres->has($slug)) {
                    $this->command->warn("Genre with slug '{$slug}' not found. Skipping character '{$character->name}' for this genre.");

                    continue;
                }

                $characterUuid = Arr::get($character->details(), 'uuid');

                $insertion = [
                    'uuid' => $characterUuid,
                    'user_id' => null,
                    'genre_id' => $genres->get($slug)->id,
                    'name' => $character->name,
                    'slug' => Str::slug($character->value),
                    'description' => Arr::get($character->details(), 'description'),
                    'training_images' => null,
                    'gender' => Arr::get($character->details(), 'gender'),
                    'model' => Arr::get($genre, 'model'),
                    'trigger' => $trigger = Arr::get($genre, 'trigger'),
                    'prompt' => Arr::get($genre, 'prompt'),
                    'status' => CharacterStatus::READY->value,
                    'meta' => Arr::get($genre, 'details', []),
                    'active' => true,
                ];

                $characters[] = $insertion;

                Character::withoutEvents(function () use ($trigger, $insertion) {
                    Character::updateOrCreate(['trigger' => $trigger], $insertion);
                });
            }
        }

        File::put(storage_path(self::$files['characters']), json_encode($characters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->command->info('Characters seeded successfully. Total Characters: '.Character::count());
        $this->command->info('==========================================');
    }
}
