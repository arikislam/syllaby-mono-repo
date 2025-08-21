<?php

namespace App\Syllaby\Presets;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class Preset extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the type of the preset.
     */
    abstract public function type(): Attribute;

    /**
     * Get the user that owns the preset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
