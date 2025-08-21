<?php

namespace App\Syllaby\Characters;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use App\System\Traits\HasActiveFlag;
use App\System\Contracts\Activatable;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Syllaby\Characters\Enums\CharacterStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Character extends Model implements Activatable, HasMedia
{
    use HasActiveFlag, HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Character $character) {
            $character->uuid = Str::uuid()->toString();
            $character->status = CharacterStatus::DRAFT;
        });
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'status' => CharacterStatus::class,
        ];
    }

    public function isCustom(): bool
    {
        return filled($this->user_id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $builder, User $user): Builder
    {
        return $builder->where('user_id', $user->id)->orWhereNull('user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('poses');
        $this->addMediaCollection('sandbox');
        $this->addMediaCollection('preview')->singleFile();
        $this->addMediaCollection('reference')->singleFile();
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    protected static function newFactory(): CharacterFactory
    {
        return CharacterFactory::new();
    }
}
