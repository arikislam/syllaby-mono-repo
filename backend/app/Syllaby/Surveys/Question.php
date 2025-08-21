<?php

namespace App\Syllaby\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    const string TYPE_TAGS = 'tags';

    const string TYPE_CHECKBOX = 'checkbox';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'rules' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Gets only the active questions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Gets the question survey.
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Gets the users answers.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
