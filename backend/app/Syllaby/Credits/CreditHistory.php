<?php

namespace App\Syllaby\Credits;

use App\Syllaby\Users\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CreditHistoryFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditHistory extends Model
{
    use HasFactory;

    const int TRUNCATED_LENGTH = 40;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array'
        ];
    }

    public function creditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCreditTitleAttribute(): string|null
    {
        return match ($this->creditable_type) {
            'keyword' => $this->creditable?->keyword,
            'video' => Str::limit($this->creditable->script, 35),
            'generator' => Str::limit($this->creditable->topic, 35),
            default => '-',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditEvent(): BelongsTo
    {
        return $this->belongsTo(CreditEvent::class, 'credit_events_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CreditHistoryFactory::new();
    }
}
