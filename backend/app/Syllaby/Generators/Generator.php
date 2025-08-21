<?php

namespace App\Syllaby\Generators;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\GeneratorFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Generator extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array'
        ];
    }

    /**
     * Get the parent "geratable" model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return GeneratorFactory::new();
    }
}
