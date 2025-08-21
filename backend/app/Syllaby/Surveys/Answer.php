<?php

namespace App\Syllaby\Surveys;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    const string ANSWER_TYPE_ARRAY = 'array';

    const string ANSWER_TYPE_TEXT = 'text';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the user who answered the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the properly formatted body attribute.
     */
    protected function body(): Attribute
    {
        return Attribute::make(get: function ($body) {
            return $this->type == 'array' ? $this->stringify($body) : $body;
        });
    }

    /**
     * Format to a string answers given in an array.
     */
    private function stringify(string $body): string
    {
        $list = json_decode($body);

        return implode(', ', $list);
    }
}
