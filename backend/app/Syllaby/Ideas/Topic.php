<?php

namespace App\Syllaby\Ideas;

use App\Syllaby\Users\User;
use App\Syllaby\Bookmarks\Bookmarkable;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RelatedTopicFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
    use Bookmarkable, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'related_topics';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'ideas' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this topic.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RelatedTopicFactory
    {
        return RelatedTopicFactory::new();
    }
}
