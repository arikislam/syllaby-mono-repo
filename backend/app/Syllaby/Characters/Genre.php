<?php

namespace App\Syllaby\Characters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\System\Traits\HasActiveFlag;
use Database\Factories\GenreFactory;
use App\System\Contracts\Activatable;
use App\Syllaby\Videos\Enums\Dimension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Genre extends Model implements Activatable
{
    use HasActiveFlag, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'details' => 'array',
            'consistent_character' => 'boolean',
        ];
    }

    public function build(string $prompt, Dimension $dimension): array
    {
        $meta = $this->meta;

        Arr::set($meta, 'input.width', $dimension->get('width'));
        Arr::set($meta, 'input.height', $dimension->get('height'));
        Arr::set($meta, 'input.prompt', Str::replace('[PROMPT]', $prompt, Arr::get($meta, 'input.prompt')));

        return $meta;
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'genre_id');
    }

    protected static function newFactory(): GenreFactory
    {
        return GenreFactory::new();
    }
}
